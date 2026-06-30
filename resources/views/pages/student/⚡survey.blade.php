@php
    use function Laravel\Folio\{name};

    name('survey');
@endphp

<x-layouts::auth.card :title="__('Studentenenquete')">
    <livewire:student.survey />
</x-layouts::auth.card>
