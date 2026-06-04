<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('customer.landing');
})->name('home');

Route::get('/track', function () {
    $id = trim((string) request('id'));
    if ($id === '') {
        return redirect()->route('home');
    }
    return redirect()->route('track.show', ['id' => $id]);
})->name('track.lookup');

Route::get('/track/{id}', function (string $id) {
    $id = strtoupper(trim($id));
    $booking = config('demo-bookings.' . $id);
    abort_if($booking === null, 404, 'Booking not found in demo dataset.');
    return view('customer.track', [
        'id'      => $id,
        'booking' => $booking,
    ]);
})->name('track.show');
