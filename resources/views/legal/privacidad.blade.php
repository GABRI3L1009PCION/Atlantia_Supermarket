@extends('legal.layout', [
    'documento' => 'privacidad',
    'title' => 'Política de Privacidad | Atlantia Store',
    'titulo' => 'Política de Privacidad',
    'resumen' => 'Conoce cómo recopilamos, usamos, protegemos y tratamos tus datos personales en Atlantia Store, para brindarte una experiencia de compra segura, confiable y transparente.',
    'secciones' => [
        'responsable' => 'Responsable del tratamiento',
        'datos' => 'Datos que recopilamos',
        'finalidades' => 'Finalidades de uso',
        'compartir' => 'Compartición de información',
        'ubicacion' => 'Ubicación GPS',
        'pagos-datos' => 'Datos de pago',
        'seguridad' => 'Seguridad de la información',
        'conservacion' => 'Conservación de datos',
        'derechos' => 'Derechos del usuario',
        'comunicaciones' => 'Comunicaciones y publicidad',
        'cookies' => 'Cookies y tecnologías similares',
        'menores' => 'Menores de edad',
        'cambios-privacidad' => 'Cambios a esta política',
    ],
])

@section('documento')
    @php
        $legal = config('atlantia.legal');
        $titular = e($legal['razon_social'] ?: $legal['nombre_comercial']);
        $correo = $legal['email_privacidad'];
    @endphp

    <x-legal.section id="responsable" n="1" title="Responsable del tratamiento" icon="pin" inline
        callout="<strong>Razón social:</strong> {{ $titular }} &nbsp;|&nbsp; <strong>Domicilio:</strong> {{ $legal['domicilio'] }}">
        <p><strong>{{ $legal['razon_social'] ?: $legal['nombre_comercial'] }}</strong> ("Atlantia", "nosotros" o "la plataforma"), que opera Atlantia Store, es responsable del tratamiento de tus datos personales. Los tratamos con confidencialidad y conforme a la Constitución Política de la República de Guatemala y las leyes aplicables.</p>
        <p>Los comercios afiliados que reciben tu pedido son responsables, a su vez, de los datos que usan para prepararlo y facturarlo.</p>
    </x-legal.section>

    <x-legal.section id="datos" n="2" title="Datos que recopilamos" icon="idcard"
        callout="Recopilamos solo los datos necesarios para operar y mejorar el servicio.">
        <h3>Clientes</h3>
        <ul>
            <li><strong>Identificación y contacto:</strong> nombre, teléfono y, si lo das o creas una cuenta, correo electrónico.</li>
            <li><strong>Entrega:</strong> dirección, colonia o barrio, referencias y ubicación GPS del punto de entrega.</li>
            <li><strong>Facturación:</strong> NIT, nombre o razón social y correo para la factura, si pides factura con tus datos.</li>
            <li><strong>Pedidos:</strong> productos, montos, método de pago, historial de compras, reclamos y calificaciones.</li>
            <li><strong>Cuenta:</strong> contraseña (guardada cifrada, nunca en texto legible) y preferencias.</li>
            <li><strong>Datos técnicos:</strong> dirección IP, tipo de dispositivo y navegador, y registros de actividad necesarios para seguridad y prevención de fraude.</li>
        </ul>
        <h3>Repartidores</h3>
        <ul>
            <li>Datos de identificación, contacto, vehículo y documentos para validar su registro.</li>
            <li>Ubicación en tiempo real <strong>solo mientras está en turno o con un pedido asignado</strong>.</li>
            <li>Registro de entregas, cobros y ganancias.</li>
        </ul>
        <h3>Comercios</h3>
        <ul>
            <li>Datos del negocio y de su representante, información fiscal, cuenta bancaria para liquidaciones y documentos presentados en la solicitud de afiliación.</li>
        </ul>
    </x-legal.section>

    <x-legal.section id="finalidades" n="3" title="Finalidades de uso" icon="list"
        callout="Usamos tus datos para que tu pedido llegue, se cobre y se facture bien.">
        <ul>
            <li>Procesar, preparar, entregar y dar seguimiento a tus pedidos.</li>
            <li>Validar que tu dirección esté dentro de la zona de cobertura y calcular el costo de envío.</li>
            <li>Cobrar, reembolsar y emitir facturas FEL.</li>
            <li>Comunicarnos contigo sobre tu pedido por llamada, WhatsApp, mensaje, correo o notificación (por ejemplo: pedido confirmado, en camino o entregado). Si no quieres recibir avisos por WhatsApp, respóndenos “No deseo avisos” y dejaremos de enviarlos.</li>
            <li>Atender soporte, reclamos y devoluciones.</li>
            <li>Prevenir fraudes, abusos y accesos no autorizados, y proteger a clientes, repartidores y comercios.</li>
            <li>Mejorar el servicio con estadísticas internas, por ejemplo para predecir demanda o tiempos de entrega.</li>
            <li>Cumplir obligaciones legales, tributarias y requerimientos de autoridades competentes.</li>
            <li>Enviarte promociones, <strong>solo si lo autorizaste</strong>.</li>
        </ul>
    </x-legal.section>

    <x-legal.section id="compartir" n="4" title="Compartición de información" icon="store"
        callout="Compartimos solo la información necesaria para cumplir tu pedido. No vendemos tus datos.">
        <p><strong>No vendemos ni alquilamos tus datos personales.</strong> Solo los compartimos en la medida necesaria con:</p>
        <ul>
            <li><strong>El comercio de tu pedido:</strong> tu nombre, los productos y los datos de facturación, para prepararlo y emitir la factura.</li>
            <li><strong>El repartidor asignado:</strong> tu nombre, teléfono, dirección, referencias y ubicación de entrega, únicamente para entregar el pedido.</li>
            <li><strong>Procesadores de pago</strong> (como Recurrente), para cobrar con tarjeta.</li>
            <li><strong>El certificador FEL y la SAT</strong>, para certificar las facturas.</li>
            <li><strong>Proveedores tecnológicos</strong> que nos prestan servicios de alojamiento, correo electrónico, mensajería (WhatsApp), mapas, notificaciones y seguridad, bajo obligación de confidencialidad.</li>
            <li><strong>Autoridades</strong>, cuando una ley o una orden judicial lo exija.</li>
        </ul>
        <p>Algunos de estos proveedores pueden almacenar información en servidores fuera de Guatemala; en ese caso exigimos medidas de seguridad equivalentes.</p>
    </x-legal.section>

    <x-legal.section id="ubicacion" n="5" title="Ubicación GPS" icon="pin"
        callout="Solo pedimos tu ubicación cuando tú lo autorizas.">
        <p>Pedimos tu ubicación <strong>solo cuando tú lo autorizas</strong> en tu navegador o teléfono, para fijar el punto exacto de entrega. No rastreamos tu ubicación en segundo plano. Puedes retirar el permiso en la configuración de tu dispositivo, aunque sin una ubicación válida es posible que no podamos completar la entrega.</p>
    </x-legal.section>

    <x-legal.section id="pagos-datos" n="6" title="Datos de pago" icon="card"
        callout="Nunca guardamos el número completo ni el código de tu tarjeta.">
        <p>Los pagos con tarjeta los procesa directamente el proveedor de pagos. <strong>Atlantia no recibe ni guarda el número completo, la fecha de vencimiento ni el código de seguridad de tu tarjeta</strong>; solo conservamos la referencia y el estado del pago para conciliar y, si corresponde, reembolsar.</p>
    </x-legal.section>

    <x-legal.section id="seguridad" n="7" title="Seguridad de la información" icon="lock"
        callout="Tu información está protegida con medidas de seguridad robustas.">
        <ul>
            <li>Toda la comunicación con la plataforma viaja cifrada (HTTPS).</li>
            <li>Las contraseñas se guardan cifradas con algoritmos de una sola vía.</li>
            <li>El personal accede solo a la información necesaria para su función, con permisos por rol y verificación en dos pasos.</li>
            <li>Registramos accesos y operaciones sensibles para detectar usos indebidos.</li>
        </ul>
        <p>Ningún sistema es completamente infalible. Si ocurriera un incidente de seguridad que afecte tus datos, te lo informaremos y tomaremos medidas para reducir el impacto.</p>
    </x-legal.section>

    <x-legal.section id="conservacion" n="8" title="Conservación de datos" icon="clock"
        callout="Pasados los plazos legales, los datos se eliminan o anonimizan.">
        <ul>
            <li>Los datos de tu cuenta, mientras esté activa.</li>
            <li>Los pedidos, pagos y facturas, durante el plazo que exige la legislación tributaria y mercantil, aunque cierres tu cuenta.</li>
            <li>Los datos de compras como invitado, el tiempo necesario para completar la entrega, atender reclamos y cumplir esas mismas obligaciones.</li>
            <li>La ubicación en tiempo real de repartidores, solo el tiempo necesario para la operación y la resolución de incidencias.</li>
        </ul>
    </x-legal.section>

    <x-legal.section id="derechos" n="9" title="Derechos del usuario" icon="scale"
        callout="Puedes gestionar y ejercer tus derechos sobre tus datos en cualquier momento.">
        <p>Puedes pedirnos en cualquier momento:</p>
        <ul>
            <li><strong>Acceso:</strong> saber qué datos tuyos tenemos y para qué los usamos.</li>
            <li><strong>Rectificación:</strong> corregir datos incorrectos o incompletos (muchos los puedes cambiar desde tu cuenta).</li>
            <li><strong>Eliminación:</strong> que borremos tu cuenta y tus datos, salvo los que debamos conservar por ley.</li>
            <li><strong>Oposición:</strong> dejar de recibir publicidad o que no usemos tus datos para fines no esenciales.</li>
        </ul>
        <p>Envía tu solicitud @if ($correo) a <a href="mailto:{{ $correo }}">{{ $correo }}</a> @else desde nuestra <a href="{{ route('contacto') }}">página de contacto</a> @endif indicando tu nombre y teléfono o correo registrado. Para proteger tu información, podemos pedirte que confirmes tu identidad. Respondemos en un plazo máximo de <strong>10 días hábiles</strong>.</p>
    </x-legal.section>

    <x-legal.section id="comunicaciones" n="10" title="Comunicaciones y publicidad" icon="mail"
        callout="Solo te enviamos promociones si lo aceptaste.">
        <p>Los mensajes sobre el estado de tus pedidos, seguridad de tu cuenta y facturas son parte del servicio. Las promociones y novedades solo te las enviamos si lo aceptaste, y puedes dejar de recibirlas en cualquier momento desde tu cuenta o el enlace incluido en cada mensaje.</p>
    </x-legal.section>

    <x-legal.section id="cookies" n="11" title="Cookies y tecnologías similares" icon="cookie"
        callout="No usamos cookies de publicidad de terceros.">
        <p>Usamos cookies y almacenamiento del navegador necesarios para mantener tu sesión, tu carrito, la seguridad de los formularios y tus preferencias. Si las bloqueas, algunas funciones, como el carrito o el inicio de sesión, pueden dejar de funcionar.</p>
    </x-legal.section>

    <x-legal.section id="menores" n="12" title="Menores de edad" icon="user">
        <p>La plataforma está dirigida a personas mayores de 18 años. No recopilamos de forma intencional datos de menores. Si eres padre, madre o tutor y crees que un menor nos dio sus datos, contáctanos para eliminarlos.</p>
    </x-legal.section>

    <x-legal.section id="cambios-privacidad" n="13" title="Cambios a esta política" icon="edit">
        <p>Si cambiamos esta política, publicaremos la nueva versión en esta página con su fecha de vigencia. Si el cambio es importante, también te lo avisaremos por correo o dentro de la plataforma.</p>
    </x-legal.section>
@endsection
