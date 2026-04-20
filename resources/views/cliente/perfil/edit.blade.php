@extends('layouts.marketplace')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Mi perfil" subtitle="Datos personales y preferencias." /><x-data-panel title="Perfil" :items="$perfil" /></section>
@endsection

