<?php

namespace App\Http\Controllers\Repartidor;

use App\Http\Controllers\Controller;
use App\Models\CalificacionRepartidor;
use App\Models\CourierProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Mi perfil del repartidor: sus datos de contacto, su vehículo y lo que opinan sus clientes.
 */
class PerfilController extends Controller
{
    public const VEHICULOS = ['Moto', 'Bicicleta', 'Carro', 'Pick-up', 'A pie'];

    public function edit(Request $request): View
    {
        $user = $request->user();
        $perfil = CourierProfile::query()->firstOrCreate(['user_id' => $user->id], ['rating' => 5]);
        $calificaciones = CalificacionRepartidor::query()->where('repartidor_id', $user->id);

        return view('repartidor.perfil', [
            'user' => $user,
            'perfil' => $perfil,
            'vehiculos' => self::VEHICULOS,
            'totalCalificaciones' => (clone $calificaciones)->count(),
            'distribucion' => (clone $calificaciones)->selectRaw('estrellas, count(*) as total')->groupBy('estrellas')->pluck('total', 'estrellas'),
            'comentarios' => (clone $calificaciones)->whereNotNull('comentario')->latest('id')->limit(10)->get(['estrellas', 'comentario', 'created_at']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'regex:/^[0-9+\s\-]{8,20}$/'],
            'vehicle_type' => ['required', Rule::in(self::VEHICULOS)],
        ], [
            'phone.required' => 'Escribe tu teléfono para que la tienda y el cliente puedan llamarte.',
            'phone.regex' => 'Escribe un teléfono válido, por ejemplo 5555 1234.',
            'vehicle_type.in' => 'Elige uno de los vehículos de la lista.',
        ]);

        $request->user()->forceFill(['phone' => trim($data['phone'])])->save();
        CourierProfile::query()->updateOrCreate(['user_id' => $request->user()->id], ['vehicle_type' => $data['vehicle_type']]);

        return back()->with('success', 'Guardamos tus datos.');
    }
}
