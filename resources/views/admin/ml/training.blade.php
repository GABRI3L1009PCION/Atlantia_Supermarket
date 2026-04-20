@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Reentrenamiento ML" subtitle="Jobs de entrenamiento y despliegue de modelos." /><x-data-panel title="Entrenamientos" :items="$jobs" /></section>
@endsection

