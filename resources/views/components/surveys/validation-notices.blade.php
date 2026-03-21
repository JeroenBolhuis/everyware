@if ($errors->any())
    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-700">
        <ul class="list-disc pl-5 space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div
    id="surveyValidationMessage"
    class="hidden mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-700"
></div>

