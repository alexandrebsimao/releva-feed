@props(['title' => null])

@include('layouts.authenticated', ['slot' => $slot, 'title' => $title])
