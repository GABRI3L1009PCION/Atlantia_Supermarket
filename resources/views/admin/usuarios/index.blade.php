@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Usuarios" subtitle="Gestion de cuentas del marketplace." /><x-data-panel title="Usuarios" :items="$usuarios" /></section>
@endsection

