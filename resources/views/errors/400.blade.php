@include('errors.layout', [
    'status' => 400,
    'title' => 'Ongeldig verzoek',
    'message' => 'De aanvraag kon niet worden verwerkt. Controleer de link of probeer de pagina opnieuw te openen.',
])
