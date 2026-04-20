@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Antifraude" subtitle="Alertas de riesgo y revision manual." /><x-data-panel title="Alertas" :items="$alerts" /></section>
@endsection

