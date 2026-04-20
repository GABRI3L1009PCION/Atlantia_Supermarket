@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="{{ $usuario->name }}" subtitle="Detalle de usuario." /><x-data-panel title="Usuario" :items="$usuario" /></section>
@endsection

