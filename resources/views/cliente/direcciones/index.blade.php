@extends('layouts.marketplace')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Direcciones" subtitle="Administra tus puntos de entrega en Izabal." /><x-data-panel title="Direcciones guardadas" :items="$direcciones" /></section>
@endsection

