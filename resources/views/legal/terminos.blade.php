@extends('legal.layout', [
    'documento' => 'terminos',
    'title' => 'Términos y Condiciones | Atlantia Store',
    'titulo' => 'Términos y Condiciones de Uso y Compra',
    'resumen' => 'Estas son las reglas para comprar en Atlantia Store: cómo funcionan los pedidos, las entregas, los pagos, las devoluciones y hasta dónde llega nuestra responsabilidad como plataforma que conecta a clientes con comercios afiliados.',
    'secciones' => [
        'alcance' => 'Quiénes somos y alcance',
        'aceptacion' => 'Aceptación de estos términos',
        'intermediario' => 'Atlantia Store es un marketplace',
        'cuentas' => 'Cuentas y compras como invitado',
        'precios' => 'Precios, IVA y disponibilidad',
        'pedidos' => 'Pedidos y confirmación',
        'entregas' => 'Cobertura y entregas',
        'pagos' => 'Formas de pago',
        'cancelaciones' => 'Cancelaciones y reembolsos',
        'reclamos' => 'Reclamos por productos',
        'incumplimiento' => 'Si un comercio no envía tu pedido',
        'responsabilidad' => 'Limitación de responsabilidad',
        'restringidos' => 'Productos con restricciones',
        'conducta' => 'Uso correcto de la plataforma',
        'promociones' => 'Cupones y promociones',
        'facturacion' => 'Facturación FEL',
        'comercios' => 'Condiciones para comercios afiliados',
        'propiedad' => 'Propiedad intelectual',
        'cambios' => 'Cambios a estos términos',
        'ley' => 'Ley aplicable y reclamos',
    ],
])

@section('documento')
    <x-legal.section id="alcance" n="1" title="Quiénes somos y alcance" icon="pin" inline
        callout="<strong>Área de cobertura:</strong> Puerto Barrios y Santo Tomás de Castilla, Izabal.">
        <p><strong>Atlantia Store</strong> ("Atlantia", "nosotros") es una plataforma digital que permite a los clientes comprar productos de supermercados, tiendas y comercios afiliados ("comercios" o "vendedores") y recibirlos a domicilio mediante repartidores registrados en la plataforma.</p>
        <p>Estos términos aplican al sitio web, a las aplicaciones de Atlantia y a cualquier compra hecha por medio de ellos.</p>
    </x-legal.section>

    <x-legal.section id="aceptacion" n="2" title="Aceptación de estos términos" icon="shield"
        callout="Al confirmar tu compra aceptas estos términos y la Política de Privacidad.">
        <p>Al crear una cuenta, comprar como invitado o marcar la casilla de aceptación en el proceso de compra, confirmas que leíste y aceptas estos Términos y Condiciones y nuestra <a href="{{ route('legal.privacidad') }}">Política de Privacidad</a>. Si no estás de acuerdo, no debes usar la plataforma.</p>
        <p>Para comprar debes ser mayor de 18 años o contar con la autorización de tu padre, madre o tutor, quien será responsable de la compra.</p>
    </x-legal.section>

    <x-legal.section id="intermediario" n="3" title="Atlantia Store es un marketplace" icon="store"
        callout="Conectamos clientes con comercios afiliados de confianza.">
        <p>Atlantia <strong>no es el fabricante ni, salvo que se indique expresamente, el vendedor</strong> de los productos publicados. Cada producto lo vende el comercio que aparece en su ficha. Atlantia pone a disposición la plataforma, procesa el pedido, coordina la entrega y atiende los reclamos como intermediario.</p>
        <p>Cada comercio es responsable de:</p>
        <ul>
            <li>La calidad, estado, fecha de vencimiento, inocuidad y legalidad de los productos que vende.</li>
            <li>La exactitud de las descripciones, fotografías, pesos, presentaciones y precios que publica.</li>
            <li>Mantener actualizado su inventario y preparar el pedido completo y a tiempo.</li>
            <li>Las garantías del producto y la emisión de su propia factura electrónica (FEL).</li>
        </ul>
        <p>Las fotografías son ilustrativas; el empaque o la presentación pueden variar sin que eso cambie el contenido del producto.</p>
    </x-legal.section>

    <x-legal.section id="cuentas" n="4" title="Cuentas y compras como invitado" icon="user"
        callout="Tus datos deben ser verdaderos para que tu pedido llegue sin problemas.">
        <p>Puedes comprar con una cuenta o como invitado. En ambos casos te comprometes a dar datos <strong>verdaderos, completos y actualizados</strong>: nombre, teléfono, dirección y ubicación de entrega.</p>
        <ul>
            <li>Eres responsable de mantener en secreto tu contraseña y de toda actividad que se haga desde tu cuenta.</li>
            <li>Si sospechas que alguien usó tu cuenta sin permiso, avísanos de inmediato.</li>
            <li>Atlantia puede suspender o cancelar cuentas con datos falsos, actividad fraudulenta o incumplimiento de estos términos.</li>
        </ul>
    </x-legal.section>

    <x-legal.section id="precios" n="5" title="Precios, IVA y disponibilidad" icon="tag"
        callout="Precios en quetzales (Q) con IVA del 12% incluido.">
        <ul>
            <li>Todos los precios están en <strong>quetzales (GTQ)</strong> e <strong>incluyen el IVA del 12 %</strong>. El IVA se muestra desglosado en el resumen y en la factura, pero no se suma al precio publicado.</li>
            <li>El costo de envío se muestra antes de confirmar la compra y depende de la zona de entrega.</li>
            <li>Los precios y promociones pueden cambiar en cualquier momento, pero se respeta el precio vigente al confirmar tu pedido.</li>
            <li>Si un precio tiene un <strong>error evidente</strong> (por ejemplo, un producto de Q 150.00 publicado en Q 1.50), el comercio puede anular esa línea del pedido y te devolveremos lo que hayas pagado por ella.</li>
            <li>Mostramos solo productos con existencias, pero el inventario puede cambiar mientras compras. Si un producto se agota después de tu pedido, te avisaremos y no se te cobrará; si ya pagaste, se te reembolsa ese monto.</li>
        </ul>
    </x-legal.section>

    <x-legal.section id="pedidos" n="6" title="Pedidos y confirmación" icon="receipt"
        callout="Si cancelamos parte de tu pedido, se te reembolsa lo pagado.">
        <p>Tu pedido queda registrado al confirmar la compra y recibirás un número de seguimiento. El pedido se considera <strong>aceptado</strong> cuando el comercio lo confirma para su preparación.</p>
        <p>Un pedido puede ser rechazado o cancelado, total o parcialmente, cuando:</p>
        <ul>
            <li>El producto no tiene existencias o el comercio no puede prepararlo.</li>
            <li>La dirección o ubicación está fuera de la zona de cobertura o no se puede verificar.</li>
            <li>El pago no se acredita o es rechazado por el banco o la pasarela de pagos.</li>
            <li>Detectamos señales razonables de fraude, suplantación de identidad o abuso de promociones.</li>
        </ul>
    </x-legal.section>

    <x-legal.section id="entregas" n="7" title="Cobertura y entregas" icon="truck"
        callout="<strong>Importante:</strong> asegúrate de ingresar una dirección completa y precisa para garantizar una entrega exitosa.">
        <ul>
            <li>Entregamos únicamente en <strong>Puerto Barrios y Santo Tomás de Castilla, Izabal</strong>. La ubicación GPS se usa para validar la cobertura y guiar al repartidor.</li>
            <li>Los tiempos de entrega son <strong>estimados</strong>. Pueden variar por volumen de pedidos, lluvia, tráfico, cierres de calles, cortes de energía u otras situaciones fuera de nuestro control.</li>
            <li>Debes estar disponible en la dirección indicada, o dejar a una persona autorizada, y tener el pago listo si elegiste pago contra entrega.</li>
            <li>Para entregar se puede solicitar el <strong>código de entrega</strong> de tu pedido. Quien muestre ese código se considera autorizado para recibirlo.</li>
            <li>Si el repartidor llega y no es posible entregar (nadie atiende, dirección incorrecta o incompleta, rechazo sin causa válida), se intentará contactarte. Si no hay respuesta en un tiempo razonable, el pedido podrá cancelarse y el costo de envío no será reembolsable.</li>
            <li>Al recibir el pedido, revísalo frente al repartidor. A partir de la entrega, el cuidado y la conservación de los productos (especialmente refrigerados y congelados) son tu responsabilidad.</li>
            <li>Los productos vendidos por <strong>Atlantia Store</strong> se entregan <strong>solo a domicilio</strong>.</li>
            <li>La opción <strong>recoger en tienda</strong> está disponible únicamente con comercios afiliados que tienen local; el pedido se guardará durante el horario indicado y los productos perecederos pueden no reservarse por más de un día.</li>
        </ul>
    </x-legal.section>

    <x-legal.section id="pagos" n="8" title="Formas de pago" icon="card"
        callout="Atlantia no guarda los datos completos de tu tarjeta.">
        <ul>
            <li><strong>Efectivo contra entrega.</strong> Si necesitas cambio, indícalo al comprar; el repartidor puede no llevar cambio para billetes de alta denominación.</li>
            <li><strong>Transferencia bancaria.</strong> El pedido se entrega cuando se confirma la transferencia con la referencia indicada.</li>
            <li><strong>Tarjeta de crédito o débito.</strong> Los pagos en línea se procesan por medio de <strong>Recurrente</strong>, un procesador de pagos externo. Atlantia <strong>no almacena</strong> los datos completos de tu tarjeta. Los pedidos con pago en línea se despachan solo cuando el pago está acreditado.</li>
        </ul>
        <p>Los cargos rechazados, retenidos o revertidos por tu banco se rigen por las condiciones de tu banco y del procesador de pagos.</p>
    </x-legal.section>

    <x-legal.section id="cancelaciones" n="9" title="Cancelaciones y reembolsos" icon="refresh"
        callout="Cancela sin costo mientras tu pedido no haya salido a entrega.">
        <ul>
            <li>Puedes cancelar sin costo <strong>mientras el pedido no haya salido a entrega</strong>, desde tu cuenta o comunicándote con soporte.</li>
            <li>Una vez que el pedido salió con el repartidor, la cancelación puede generar el cobro del envío.</li>
            <li>Los reembolsos se hacen por el <strong>mismo medio de pago</strong>. Para tarjeta, el tiempo en que se refleja depende de tu banco (normalmente entre 5 y 15 días hábiles). Para efectivo o transferencia, coordinaremos la devolución contigo.</li>
        </ul>
    </x-legal.section>

    <x-legal.section id="reclamos" n="10" title="Reclamos por productos" icon="alert"
        callout="Reporta con fotos: 24 horas para perecederos y 3 días para lo demás.">
        <p>Si recibes un producto <strong>dañado, vencido, incorrecto o incompleto</strong>, repórtalo desde tu pedido o a soporte con fotografías y el número de pedido:</p>
        <ul>
            <li>Productos perecederos (carnes, lácteos, frutas, verduras, refrigerados y congelados): dentro de las <strong>24 horas</strong> siguientes a la entrega.</li>
            <li>Demás productos: dentro de los <strong>3 días</strong> siguientes a la entrega, sin abrir ni usar, salvo que el defecto solo se descubra al abrirlo.</li>
        </ul>
        <p>Revisaremos el caso con el comercio y, si procede, se repondrá el producto o se reembolsará su valor. No aplican devoluciones por cambio de opinión en productos perecederos, de higiene personal o abiertos, salvo defecto. Esto no limita los derechos que la ley te reconoce como consumidor.</p>
    </x-legal.section>

    <x-legal.section id="incumplimiento" n="11" title="Si un comercio no envía tu pedido" icon="store"
        callout="Lo que no se entregue no se cobra, y si ya pagaste se te devuelve completo.">
        <p>Si un comercio no prepara o no entrega todo o parte de tu pedido (por ejemplo, porque no tenía el producto, cerró antes de tiempo o no respondió), Atlantia:</p>
        <ol>
            <li>Te avisará en cuanto lo detecte.</li>
            <li>Cancelará la parte no entregada y <strong>no te la cobrará</strong>; si ya la pagaste, gestionará el <strong>reembolso total</strong> de ese monto, incluido el envío correspondiente.</li>
            <li>Podrá ofrecerte, con tu autorización, sustituir el producto por otro similar del mismo u otro comercio.</li>
        </ol>
        <p>Como intermediario, la responsabilidad de Atlantia en estos casos se limita a lo anterior. Atlantia no responde por perjuicios adicionales derivados del incumplimiento del comercio, como compras sustitutas a mayor precio, eventos cancelados o pérdidas de tiempo. Puedes ejercer directamente contra el comercio los derechos que te correspondan, y nosotros te facilitaremos sus datos.</p>
    </x-legal.section>

    <x-legal.section id="responsabilidad" n="12" title="Limitación de responsabilidad" icon="scale"
        callout="Nada de esto limita tus derechos como consumidor (Decreto 006-2003).">
        <p>Atlantia trabaja para que el servicio funcione bien, pero <strong>no se hace responsable</strong> de:</p>
        <ul>
            <li>Defectos de fabricación, calidad, inocuidad, contenido o etiquetado de los productos, que corresponden al fabricante y al comercio que los vende.</li>
            <li>Errores en descripciones, fotografías o precios publicados por los comercios, salvo lo indicado en la sección 5.</li>
            <li>Retrasos o imposibilidad de entrega causados por <strong>datos incorrectos o incompletos</strong> del cliente, o por no estar disponible para recibir.</li>
            <li>Retrasos o interrupciones por <strong>caso fortuito o fuerza mayor</strong>: lluvias fuertes, inundaciones, tormentas, bloqueos, manifestaciones, cortes de energía o de internet, emergencias sanitarias o decisiones de autoridades.</li>
            <li>Fallas de servicios de terceros que no controlamos, como bancos, procesadores de pago, operadores de telefonía, proveedores de mapas o el certificador de facturas FEL.</li>
            <li>Reacciones alérgicas o efectos por consumo cuando el cliente no revisó la etiqueta, los ingredientes o las indicaciones del producto.</li>
            <li>El manejo, almacenamiento o uso de los productos después de entregados.</li>
            <li>Daños indirectos, lucro cesante o pérdidas que no sean consecuencia directa de un pedido.</li>
        </ul>
        <p>En cualquier caso en que Atlantia resulte responsable, su responsabilidad total no excederá el <strong>monto pagado por el pedido</strong> que originó el reclamo.</p>
        <p><strong>Nada de lo dispuesto en estos términos limita ni renuncia a los derechos irrenunciables que la Ley de Protección al Consumidor y Usuario (Decreto 006-2003) y demás leyes de Guatemala reconocen a los consumidores.</strong></p>
    </x-legal.section>

    <x-legal.section id="restringidos" n="13" title="Productos con restricciones" icon="idcard"
        callout="Alcohol y tabaco solo para mayores de 18 años, con DPI.">
        <ul>
            <li>Las <strong>bebidas alcohólicas y productos de tabaco</strong> solo se venden y entregan a mayores de 18 años. El repartidor puede solicitar el DPI al entregar y negarse a entregar esos productos si no se comprueba la edad; en ese caso se reembolsará su valor.</li>
            <li>La venta de estos productos respeta los horarios y restricciones que establezcan las autoridades (por ejemplo, la ley seca).</li>
            <li>Atlantia no vende medicamentos que requieran receta médica.</li>
        </ul>
    </x-legal.section>

    <x-legal.section id="conducta" n="14" title="Uso correcto de la plataforma" icon="ban"
        callout="El fraude o el maltrato a repartidores lleva a la suspensión de la cuenta.">
        <p>No está permitido:</p>
        <ul>
            <li>Hacer pedidos falsos, usar datos o tarjetas de otras personas, o intentar fraudes con reembolsos o cupones.</li>
            <li>Insultar, amenazar o agredir a repartidores, comercios o personal de soporte.</li>
            <li>Intentar acceder sin autorización a sistemas, cuentas o información de otros usuarios, o interferir con el funcionamiento de la plataforma.</li>
        </ul>
        <p>El incumplimiento puede llevar a la cancelación de pedidos, la suspensión de la cuenta y, si corresponde, a dar aviso a las autoridades.</p>
    </x-legal.section>

    <x-legal.section id="promociones" n="15" title="Cupones y promociones" icon="gift">
        <p>Cada cupón o promoción tiene sus propias condiciones (vigencia, monto mínimo, productos participantes y límite de usos). Los cupones no son canjeables por dinero, no son acumulables salvo que se indique y pueden anularse si se obtuvieron o usaron de forma indebida.</p>
    </x-legal.section>

    <x-legal.section id="facturacion" n="16" title="Facturación FEL" icon="receipt"
        callout="Recibes una factura FEL de cada comercio de tu pedido.">
        <p>Cada comercio emite su propia <strong>Factura Electrónica en Línea (FEL)</strong> certificada ante la SAT por los productos que vende. Si tu pedido incluye productos de varios comercios, recibirás una factura de cada uno. Puedes pedir factura con tu NIT o como Consumidor Final (CF). Revisa tus datos fiscales antes de confirmar, porque una factura certificada solo puede corregirse mediante anulación conforme a las reglas de la SAT.</p>
    </x-legal.section>

    <x-legal.section id="comercios" n="17" title="Condiciones para comercios afiliados" icon="store"
        callout="Los comercios responden por sus productos y por cumplir los pedidos aceptados.">
        <p>Los comercios que venden en Atlantia, además de estos términos, aceptan que:</p>
        <ul>
            <li>Están legalmente constituidos, inscritos en la SAT y autorizados para vender los productos que publican.</li>
            <li>Publicarán precios con IVA incluido, descripciones verdaderas e inventario actualizado.</li>
            <li>Prepararán los pedidos aceptados de forma completa, en buen estado y dentro del tiempo indicado.</li>
            <li>Responderán por la calidad, inocuidad, vencimiento y garantía de sus productos, y por emitir la factura FEL correspondiente.</li>
            <li>Si no cumplen un pedido aceptado, Atlantia podrá reembolsar al cliente y descontar ese monto, junto con las comisiones y costos de envío aplicables, de las liquidaciones del comercio.</li>
            <li>Las comisiones, plazos de liquidación y demás condiciones comerciales se rigen por el plan y el acuerdo aprobado para cada comercio.</li>
            <li>Atlantia puede suspender a comercios con incumplimientos repetidos, reclamos fundados o productos ilegales o peligrosos.</li>
        </ul>
    </x-legal.section>

    <x-legal.section id="propiedad" n="18" title="Propiedad intelectual" icon="copyright">
        <p>La marca Atlantia Store, su logotipo, diseño y software pertenecen a sus titulares y no pueden copiarse ni usarse sin autorización. Las marcas y fotografías de los productos pertenecen a sus respectivos dueños o a los comercios que las publican.</p>
    </x-legal.section>

    <x-legal.section id="cambios" n="19" title="Cambios a estos términos" icon="edit">
        <p>Podemos actualizar estos términos para reflejar cambios en el servicio o en la ley. La versión vigente siempre estará publicada en esta página con su fecha. Los cambios no afectan pedidos ya confirmados, que se rigen por los términos vigentes al momento de la compra.</p>
    </x-legal.section>

    <x-legal.section id="ley" n="20" title="Ley aplicable y reclamos" icon="gavel"
        callout="También puedes acudir a la DIACO como consumidor.">
        <p>Estos términos se rigen por las leyes de la República de Guatemala. Antes de cualquier otra vía, te pedimos comunicarte con nuestro soporte para buscar una solución. Como consumidor, también puedes acudir a la <strong>Dirección de Atención y Asistencia al Consumidor (DIACO)</strong>. Cualquier controversia se someterá a los tribunales competentes del departamento de Izabal, salvo que la ley disponga otra cosa.</p>
    </x-legal.section>
@endsection
