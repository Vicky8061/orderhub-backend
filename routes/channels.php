<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('orders.kitchen', function ($user) {
    return $user && ($user->isAdmin() || $user->isKitchen() || $user->isCashier());
});
