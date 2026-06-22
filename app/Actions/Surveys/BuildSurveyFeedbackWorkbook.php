<?php

namespace App\Actions\Surveys;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

class BuildSurveyFeedbackWorkbook
{
    private const CONTENT_TYPES_NAMESPACE = 'http://schemas.openxmlformats.org/package/2006/content-types';

    private const PACKAGE_RELATIONSHIPS_NAMESPACE = 'http://schemas.openxmlformats.org/package/2006/relationships';

    private const OFFICE_DOCUMENT_RELATIONSHIPS_NAMESPACE = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    private const WORKSHEET_NAMESPACE = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    private const CONTENT_TYPE_DEFAULTS = [
        ['rels', 'application/vnd.openxmlformats-package.relationships+xml'],
        ['xml', 'application/xml'],
    ];

    private const CONTENT_TYPE_OVERRIDES = [
        ['/xl/styles.xml', 'application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml'],
        ['/xl/workbook.xml', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml'],
        ['/xl/worksheets/sheet1.xml', 'application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml'],
    ];

    private const ROOT_RELATIONSHIPS = [
        ['rId1', 'officeDocument', 'xl/workbook.xml'],
    ];

    private const WORKBOOK_RELATIONSHIPS = [
        ['rId1', 'worksheet', 'worksheets/sheet1.xml'],
        ['rId2', 'styles', 'styles.xml'],
    ];

    public function build(array $data): string
    {
        return $this->zip($this->files($data));
    }

    public function sheetName(string $title): string
    {
        $title = trim((string) preg_replace('/[\[\]\:\*\?\/\\\\]/', ' ', $title));

        return Str::limit($title !== '' ? $title : 'Feedback export', 31, '');
    }

    private function files(array $data): array
    {
        $lastCell = $this->cellReference(count($data['headers']), count($data['rows']) + 1);

        return [
            '[Content_Types].xml' => $this->contentTypesXml(),
            '_rels/.rels' => $this->rootRelationshipsXml(),
            'xl/_rels/workbook.xml.rels' => $this->workbookRelationshipsXml(),
            'xl/styles.xml' => $this->stylesXml(),
            'xl/workbook.xml' => $this->workbookXml($data['sheet']),
            'xl/worksheets/sheet1.xml' => $this->worksheetXml($data, $lastCell),
        ];
    }

    private function contentTypesXml(): string
    {
        // XLSX verwacht hier een register van alle onderdelen in het zip-bestand zodat workbook, sheet en styles goed gekoppeld worden.
        return $this->xmlDocument(
            '<Types xmlns="'.self::CONTENT_TYPES_NAMESPACE.'">',
            [
                ...array_map(fn (array $entry) => $this->contentTypeDefault($entry[0], $entry[1]), self::CONTENT_TYPE_DEFAULTS),
                ...array_map(fn (array $entry) => $this->contentTypeOverride($entry[0], $entry[1]), self::CONTENT_TYPE_OVERRIDES),
            ],
            '</Types>',
        );
    }

    private function rootRelationshipsXml(): string
    {
        return $this->relationshipsXml(self::ROOT_RELATIONSHIPS);
    }

    private function workbookRelationshipsXml(): string
    {
        return $this->relationshipsXml(self::WORKBOOK_RELATIONSHIPS);
    }

    private function stylesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
 <fonts count="2">
  <font><sz val="11"/><name val="Calibri"/></font>
  <font><b/><sz val="11"/><name val="Calibri"/></font>
 </fonts>
 <fills count="2">
  <fill><patternFill patternType="none"/></fill>
  <fill><patternFill patternType="gray125"/></fill>
 </fills>
 <borders count="1">
  <border/>
 </borders>
 <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
 <cellXfs count="2">
  <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>
  <xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>
 </cellXfs>
 <cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>
 <dxfs count="0"/>
 <tableStyles count="0" defaultTableStyle="TableStyleMedium2" defaultPivotStyle="PivotStyleLight16"/>
</styleSheet>
XML;
    }

    private function workbookXml(string $sheet): string
    {
        $sheet = $this->escape($sheet);

        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
 <sheets><sheet name="{$sheet}" sheetId="1" r:id="rId1"/></sheets>
</workbook>
XML;
    }

    private function worksheetXml(array $data, string $lastCell): string
    {
        // Zet de exportstructuur om naar worksheet-XML met vaste kolommen, filter en een bevroren kopregel.
        $worksheetNamespace = self::WORKSHEET_NAMESPACE;
        $columns = $this->columnsXml($data['widths']);
        $rows = $this->rowsXml($data['headers'], $data['rows']);

        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="{$worksheetNamespace}">
 <dimension ref="A1:{$lastCell}"/>
 <sheetViews>
  <sheetView workbookViewId="0">
   <pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>
   <selection pane="bottomLeft" activeCell="A2" sqref="A2"/>
  </sheetView>
 </sheetViews>
 <sheetFormatPr defaultRowHeight="18"/>
 <cols>
{$columns}
 </cols>
 <sheetData>
{$rows}
 </sheetData>
 <autoFilter ref="A1:{$lastCell}"/>
</worksheet>
XML;
    }

    private function columnsXml(array $widths): string
    {
        return collect($widths)
            ->values()
            ->map(fn (int|float $width, int $index) => '  <col min="'.($index + 1).'" max="'.($index + 1).'" width="'.$this->columnWidth($width).'" customWidth="1"/>')
            ->implode("\n");
    }

    private function rowsXml(array $headers, array $rows): string
    {
        return collect([$this->row($headers, 1, 1)])
            ->concat(collect($rows)->values()->map(fn (array $row, int $index) => $this->row($row, $index + 2)))
            ->implode("\n");
    }

    private function row(array $values, int $rowNumber, int $style = 0): string
    {
        $cells = Collection::make($values)
            ->values()
            ->map(function (string $value, int $index) use ($rowNumber, $style) {
                $cell = $this->cellReference($index + 1, $rowNumber);

                return '  <c r="'.$cell.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.$this->escape($value).'</t></is></c>';
            })
            ->implode("\n");

        return "  <row r=\"{$rowNumber}\">\n{$cells}\n  </row>";
    }

    private function columnWidth(int|float $width): float
    {
        return round(max(8, ($width - 5) / 7), 2);
    }

    private function zip(array $entries): string
    {
        if ($entries === []) {
            throw new RuntimeException('Kon het XLSX-bestand niet opbouwen.');
        }

        // Bouw handmatig een minimale ZIP-container op zodat XLSX-export werkt zonder ZipArchive-extensie.
        [$time, $date] = $this->dosDateTime();
        $body = '';
        $directory = '';
        $offset = 0;
        $count = 0;

        foreach ($entries as $name => $contents) {
            $entry = $this->zipEntry((string) $name, (string) $contents, $time, $date, $offset);

            $body .= $entry['body'];
            $directory .= $entry['directory'];
            $offset += $entry['size'];
            $count++;
        }

        return $body.$directory.$this->endOfCentralDirectoryRecord($count, strlen($directory), strlen($body));
    }

    private function zipEntry(string $name, string $contents, int $time, int $date, int $offset): array
    {
        $name = str_replace('\\', '/', $name);
        $nameLength = strlen($name);
        $size = strlen($contents);
        $crc = hexdec(hash('crc32b', $contents));

        return [
            'body' => $this->localFileHeader($name, $nameLength, $contents, $size, $crc, $time, $date),
            'directory' => $this->centralDirectoryRecord($name, $nameLength, $size, $crc, $time, $date, $offset),
            'size' => 30 + $nameLength + $size,
        ];
    }

    private function localFileHeader(
        string $name,
        int $nameLength,
        string $contents,
        int $size,
        int $crc,
        int $time,
        int $date,
    ): string {
        return pack(
            'VvvvvvVVVvv',
            0x04034B50,
            20,
            0x0800,
            0,
            $time,
            $date,
            $crc,
            $size,
            $size,
            $nameLength,
            0
        ).$name.$contents;
    }

    private function centralDirectoryRecord(
        string $name,
        int $nameLength,
        int $size,
        int $crc,
        int $time,
        int $date,
        int $offset,
    ): string {
        return pack(
            'VvvvvvvVVVvvvvvVV',
            0x02014B50,
            20,
            20,
            0x0800,
            0,
            $time,
            $date,
            $crc,
            $size,
            $size,
            $nameLength,
            0,
            0,
            0,
            0,
            0,
            $offset
        ).$name;
    }

    private function endOfCentralDirectoryRecord(int $count, int $directorySize, int $bodySize): string
    {
        return pack(
            'VvvvvVVv',
            0x06054B50,
            0,
            0,
            $count,
            $count,
            $directorySize,
            $bodySize,
            0
        );
    }

    private function columnName(int $index): string
    {
        $name = '';

        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)).$name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    private function cellReference(int $columnIndex, int $rowNumber): string
    {
        return $this->columnName($columnIndex).$rowNumber;
    }

    private function escape(string $value): string
    {
        $value = preg_replace(
            '/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u',
            '',
            $value
        ) ?? '';

        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function contentTypeDefault(string $extension, string $contentType): string
    {
        return ' <Default Extension="'.$extension.'" ContentType="'.$contentType.'"/>';
    }

    private function contentTypeOverride(string $partName, string $contentType): string
    {
        return ' <Override PartName="'.$partName.'" ContentType="'.$contentType.'"/>';
    }

    private function relationshipsXml(array $relationships): string
    {
        return $this->xmlDocument(
            '<Relationships xmlns="'.self::PACKAGE_RELATIONSHIPS_NAMESPACE.'">',
            array_map(fn (array $relationship) => $this->relationship(...$relationship), $relationships),
            '</Relationships>',
        );
    }

    private function relationship(string $id, string $type, string $target): string
    {
        return ' <Relationship Id="'.$id.'" Type="'.self::OFFICE_DOCUMENT_RELATIONSHIPS_NAMESPACE.'/'.$type.'" Target="'.$target.'"/>';
    }

    private function xmlDocument(string $rootOpenTag, array $bodyLines, string $rootCloseTag): string
    {
        return implode("\n", [
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>',
            $rootOpenTag,
            ...$bodyLines,
            $rootCloseTag,
        ]);
    }

    private function dosDateTime(): array
    {
        $now = getdate();

        return [
            (($now['hours'] & 0x1F) << 11) | (($now['minutes'] & 0x3F) << 5) | (int) floor($now['seconds'] / 2),
            ((max(1980, $now['year']) - 1980) << 9) | (($now['mon'] & 0x0F) << 5) | ($now['mday'] & 0x1F),
        ];
    }
}
