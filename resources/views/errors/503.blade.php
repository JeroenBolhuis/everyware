@include('errors.layout', [
    'status' => 503,
    'title' => 'Tijdelijk niet beschikbaar',
    'message' => 'De applicatie is tijdelijk niet beschikbaar. Probeer het over een paar minuten opnieuw.',
])
