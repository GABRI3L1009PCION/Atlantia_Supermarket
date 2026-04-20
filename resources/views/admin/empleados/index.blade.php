@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Empleados" subtitle="Equipo interno de Atlantia." /><x-data-panel title="Empleados" :items="$empleados" /></section>
@endsection

