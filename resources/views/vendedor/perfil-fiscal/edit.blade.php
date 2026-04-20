@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Perfil fiscal" subtitle="Datos FEL usados para emitir DTE ante SAT." /><x-data-panel title="Perfil fiscal" :items="$perfil" /></section>
@endsection

