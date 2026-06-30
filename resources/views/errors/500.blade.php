@include('errors.layout', [
    'status' => 500,
    'title' => 'Er ging iets mis',
    'message' => 'Er is een onverwachte fout opgetreden. Probeer het later opnieuw.',
])
