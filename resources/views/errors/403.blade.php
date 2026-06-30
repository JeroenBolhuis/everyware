@include('errors.layout', [
    'status' => 403,
    'title' => 'Geen toegang',
    'message' => 'Je account heeft geen toegang tot deze pagina of actie.',
])
