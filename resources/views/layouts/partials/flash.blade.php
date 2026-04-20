@if (session('success'))
    <x-ui.alert type="success" :message="session('success')" />
@endif

@if (session('error'))
    <x-ui.alert type="error" :message="session('error')" />
@endif

@if ($errors->any())
    <x-ui.alert type="error" message="Revisa los campos marcados antes de continuar." />
@endif
