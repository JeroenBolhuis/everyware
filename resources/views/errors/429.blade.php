@include('errors.layout', [
    'status' => 429,
    'title' => 'Te veel pogingen',
    'message' => 'Er zijn te veel verzoeken kort na elkaar gedaan. Wacht even en probeer het daarna opnieuw.',
])
