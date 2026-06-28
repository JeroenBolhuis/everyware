@include('errors.layout', [
    'status' => 401,
    'title' => 'Niet ingelogd',
    'message' => 'Je moet eerst inloggen voordat je deze pagina kunt bekijken.',
])
