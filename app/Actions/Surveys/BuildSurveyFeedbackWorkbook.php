<?php

namespace App\Actions\Surveys;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

class BuildSurveyFeedbackWorkbook
{
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
        $lastCell = $this->columnName(count($data['headers'])).(count($data['rows']) + 1);

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
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
 <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
 <Default Extension="xml" ContentType="application/xml"/>
 <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
 <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
 <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>
XML;
    }

    private function rootRelationshipsXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
 <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML;
    }

    private function workbookRelationshipsXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
 <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
 <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>
XML;
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
        $columns = collect($data['widths'])
            ->values()
            ->map(fn (int|float $width, int $index) => '  <col min="'.($index + 1).'" max="'.($index + 1).'" width="'.$this->columnWidth($width).'" customWidth="1"/>')
            ->implode("\n");

        $rows = collect([$this->row($data['headers'], 1, 1)])
            ->concat(collect($data['rows'])->values()->map(fn (array $row, int $index) => $this->row($row, $index + 2)))
            ->implode("\n");

        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
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

    private function row(array $values, int $rowNumber, int $style = 0): string
    {
        $cells = Collection::make($values)
            ->values()
            ->map(function (string $value, int $index) use ($rowNumber, $style) {
                $cell = $this->columnName($index + 1).$rowNumber;

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

        [$time, $date] = $this->dosDateTime();
        $body = '';
        $directory = '';
        $offset = 0;
        $count = 0;

        foreach ($entries as $name => $contents) {
            $name = str_replace('\\', '/', $name);
            $nameLength = strlen($name);
            $size = strlen($contents);
            $crc = hexdec(hash('crc32b', $contents));

            $body .= pack(
                'VvvvvvVVVvv',
                0x04034b50,
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

            $directory .= pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014b50,
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

            $offset += 30 + $nameLength + $size;
            $count++;
        }

        return $body.$directory.pack(
            'VvvvvVVv',
            0x06054b50,
            0,
            0,
            $count,
            $count,
            strlen($directory),
            strlen($body),
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

    private function escape(string $value): string
    {
        $value = preg_replace(
            '/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u',
            '',
            $value
        ) ?? '';

        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
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
