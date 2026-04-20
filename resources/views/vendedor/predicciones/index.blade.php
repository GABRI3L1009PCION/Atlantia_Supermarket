@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Prediccion de demanda" subtitle="Pronosticos por producto." /><x-data-panel title="Predicciones" :items="$predicciones" /></section>
@endsection

