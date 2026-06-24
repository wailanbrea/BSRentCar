<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contrato de Alquiler - {{ $contract->number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333333;
            font-size: 12px;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }
        .header {
            border-bottom: 2px solid #2563EB;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header table {
            width: 100%;
        }
        .logo-text {
            font-size: 24px;
            font-weight: bold;
            color: #0B1437;
        }
        .logo-sub {
            color: #2563EB;
            font-weight: bold;
        }
        .company-info {
            text-align: right;
            font-size: 10px;
            color: #666666;
        }
        .title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            color: #0B1437;
            margin-top: 10px;
            margin-bottom: 20px;
            text-transform: uppercase;
        }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            background-color: #F3F4F6;
            color: #0B1437;
            padding: 5px 10px;
            margin-top: 15px;
            margin-bottom: 10px;
            border-left: 3px solid #2563EB;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .info-table th, .info-table td {
            border: 1px solid #E5E7EB;
            padding: 6px 10px;
            text-align: left;
            vertical-align: top;
        }
        .info-table th {
            background-color: #F9FAFB;
            font-weight: bold;
            color: #4B5563;
            width: 25%;
        }
        .pricing-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .pricing-table th, .pricing-table td {
            padding: 8px 10px;
            text-align: right;
            border-bottom: 1px solid #E5E7EB;
        }
        .pricing-table th {
            background-color: #F9FAFB;
            font-weight: bold;
            color: #4B5563;
        }
        .pricing-table .left {
            text-align: left;
        }
        .pricing-table .total-row td {
            font-weight: bold;
            font-size: 13px;
            border-top: 2px solid #2563EB;
            border-bottom: 2px solid #2563EB;
            color: #0B1437;
        }
        .terms {
            font-size: 9px;
            color: #555555;
            text-align: justify;
            margin-top: 15px;
            padding: 10px;
            background-color: #F9FAFB;
            border: 1px solid #E5E7EB;
            border-radius: 4px;
        }
        .terms p {
            margin: 0 0 8px 0;
        }
        .terms p:last-child {
            margin-bottom: 0;
        }
        .signature-section {
            margin-top: 30px;
            padding: 15px;
            border: 1px solid #2563EB;
            background-color: #EFF6FF;
            border-radius: 6px;
        }
        .signature-title {
            font-weight: bold;
            font-size: 12px;
            color: #1E3A8A;
            margin-bottom: 10px;
            border-bottom: 1px solid #BFDBFE;
            padding-bottom: 5px;
        }
        .signature-meta {
            font-family: monospace;
            font-size: 10px;
            color: #1E40AF;
        }
        .signature-meta td {
            padding: 2px 0;
            vertical-align: top;
        }
        .signature-meta .label {
            font-weight: bold;
            width: 120px;
        }
        .pending-signature {
            border: 1px dashed #EF4444;
            background-color: #FEF2F2;
            color: #B91C1C;
            text-align: center;
            font-weight: bold;
            padding: 20px;
            border-radius: 6px;
            font-size: 14px;
            margin-top: 30px;
        }
    </style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td>
                    <span class="logo-text">BS<span class="logo-sub">RentCar</span></span>
                </td>
                <td class="company-info">
                    <strong>BSRentCar S.R.L.</strong><br>
                    RNC: 1-31-XXXXX-X<br>
                    Av. Winston Churchill, Santo Domingo, RD<br>
                    Soporte: +1 (809) 555-0199 | soporte@bsrentcar.com
                </td>
            </tr>
        </table>
    </div>

    <div class="title">Contrato de Alquiler de Vehículo</div>

    <div class="section-title">Información del Contrato</div>
    <table class="info-table">
        <tr>
            <th>Número de Contrato</th>
            <td>{{ $contract->number }}</td>
            <th>Número de Reserva</th>
            <td>{{ $reservation->reservation_number }}</td>
        </tr>
        <tr>
            <th>Estado Contrato</th>
            <td style="text-transform: uppercase; font-weight: bold;">
                @if($contract->status->value === 'signed')
                    <span style="color: green;">Firmado</span>
                @else
                    <span style="color: orange;">{{ $contract->status->value }}</span>
                @endif
            </td>
            <th>Fecha Generación</th>
            <td>{{ $contract->created_at->format('d/m/Y h:i A') }}</td>
        </tr>
    </table>

    <div class="section-title">Datos del Cliente (Conductor)</div>
    <table class="info-table">
        <tr>
            <th>Nombre Completo</th>
            <td>{{ $customer->user->name }}</td>
            <th>Licencia de Conducir</th>
            <td>{{ $customer->license_number ?? 'No especificada' }}</td>
        </tr>
        <tr>
            <th>Correo Electrónico</th>
            <td>{{ $customer->user->email }}</td>
            <th>Teléfono</th>
            <td>{{ $customer->phone ?? 'No especificado' }}</td>
        </tr>
    </table>

    <div class="section-title">Datos del Alquiler y Ubicación</div>
    <table class="info-table">
        <tr>
            <th>Vehículo</th>
            <td>{{ $reservation->vehicle->brand }} {{ $reservation->vehicle->model }} ({{ $reservation->vehicle->year }})</td>
            <th>Categoría / Transmisión</th>
            <td>{{ $reservation->vehicle->category->value }} / {{ $reservation->vehicle->transmission->value }}</td>
        </tr>
        <tr>
            <th>Ubicación Recogida</th>
            <td>{{ $reservation->pickupLocation->name }} ({{ $reservation->pickup_type }})</td>
            <th>Ubicación Devolución</th>
            <td>{{ $reservation->returnLocation->name }} ({{ $reservation->return_type }})</td>
        </tr>
        <tr>
            <th>Fecha/Hora Entrega</th>
            <td>{{ $reservation->start_datetime->format('d/m/Y h:i A') }}</td>
            <th>Fecha/Hora Retorno</th>
            <td>{{ $reservation->end_datetime->format('d/m/Y h:i A') }}</td>
        </tr>
    </table>

    <div class="section-title">Desglose de Precios (USD)</div>
    <table class="pricing-table">
        <thead>
            <tr>
                <th class="left">Concepto</th>
                <th>Precio Unitario</th>
                <th>Cantidad / Días</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="left">Alquiler Diario de Vehículo</td>
                <td>${{ number_format($reservation->base_price / max(1, $reservation->start_datetime->diffInDays($reservation->end_datetime)), 2) }}</td>
                <td>{{ max(1, $reservation->start_datetime->diffInDays($reservation->end_datetime)) }} día(s)</td>
                <td>${{ number_format($reservation->base_price, 2) }}</td>
            </tr>
            @if($reservation->delivery_fee > 0)
            <tr>
                <td class="left">Cargo por Entrega a Domicilio</td>
                <td>${{ number_format($reservation->delivery_fee, 2) }}</td>
                <td>1</td>
                <td>${{ number_format($reservation->delivery_fee, 2) }}</td>
            </tr>
            @endif
            @if($reservation->insurance_fee > 0)
            <tr>
                <td class="left">Cobertura de Seguro Adicional</td>
                <td>${{ number_format($reservation->insurance_fee, 2) }}</td>
                <td>1</td>
                <td>${{ number_format($reservation->insurance_fee, 2) }}</td>
            </tr>
            @endif
            <tr>
                <td class="left">Impuesto ITBIS (18%)</td>
                <td>-</td>
                <td>-</td>
                <td>${{ number_format($reservation->tax_amount, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td class="left">Total General Renta</td>
                <td>-</td>
                <td>-</td>
                <td>${{ number_format($reservation->total_amount, 2) }} USD</td>
            </tr>
            <tr>
                <td class="left" style="color: #666; font-style: italic;">Depósito de Seguridad Retenido (Hold Tarjeta)</td>
                <td>-</td>
                <td>-</td>
                <td style="color: #666; font-style: italic;">${{ number_format($reservation->deposit_amount, 2) }} USD</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Términos y Condiciones Legales</div>
    <div class="terms">
        <p><strong>PRIMERO: OBJETO DEL CONTRATO.</strong> BSRentCar S.R.L. (en lo adelante el PROVEEDOR) entrega en calidad de alquiler al CLIENTE, quien acepta, el vehículo descrito anteriormente, en perfecto estado de funcionamiento y limpieza, comprometiéndose el CLIENTE a devolverlo en las mismas condiciones.</p>
        <p><strong>SEGUNDO: USO DEL VEHÍCULO.</strong> El CLIENTE se compromete a no utilizar el vehículo para el transporte de carga comercial, remolques, carreras, o bajo efectos de alcohol o estupefacientes. El vehículo solo podrá ser conducido por el CLIENTE o conductores debidamente autorizados por el PROVEEDOR.</p>
        <p><strong>TERCERO: DEVOLUCIÓN Y COMBUSTIBLE.</strong> El vehículo debe devolverse en la fecha y hora indicadas. La política de combustible es de Lleno a Lleno. Si el vehículo es devuelto con menos combustible del entregado, se aplicará el cargo correspondiente según tarifas de mercado. El kilometraje es ilimitado dentro del territorio nacional.</p>
        <p><strong>CUARTO: DEPÓSITO DE SEGURIDAD.</strong> El CLIENTE autoriza expresamente una retención temporal (hold) en su tarjeta de crédito por el monto detallado en este documento. Este depósito garantiza el cumplimiento de las obligaciones contractuales y podrá ser capturado total o parcialmente por el PROVEEDOR para cubrir daños no reportados, deducibles de seguro, faltantes de combustible, multas de tránsito o retrasos en la devolución.</p>
        <p><strong>QUINTO: JURISDICCIÓN Y LEY APLICABLE.</strong> Este contrato se rige bajo las leyes de la República Dominicana. Para cualquier disputa, las partes eligen como domicilio la jurisdicción de los tribunales del Distrito Nacional.</p>
    </div>

    @if($contract->status->value === 'signed')
        <div class="signature-section">
            <div class="signature-title">Firma Electrónica Simple — Aceptada Digitalmente</div>
            <table class="signature-meta" cellspacing="0" cellpadding="0">
                <tr>
                    <td class="label">Nombre Firmante:</td>
                    <td>{{ $contract->signature_meta['printed_name'] }}</td>
                </tr>
                <tr>
                    <td class="label">Fecha y Hora:</td>
                    <td>{{ $contract->signed_by_customer_at->format('d/m/Y h:i:s A') }} (UTC)</td>
                </tr>
                <tr>
                    <td class="label">Dirección IP:</td>
                    <td>{{ $contract->signature_meta['ip'] }}</td>
                </tr>
                <tr>
                    <td class="label">Navegador (UA):</td>
                    <td>{{ $contract->signature_meta['ua'] }}</td>
                </tr>
                <tr>
                    <td class="label">Integridad SHA-256:</td>
                    <td>{{ $contract->signature_meta['hash'] }}</td>
                </tr>
            </table>
        </div>
    @else
        <div class="pending-signature">
            ATENCIÓN: ESTE DOCUMENTO SE ENCUENTRA PENDIENTE DE FIRMA DIGITAL POR EL CLIENTE
        </div>
    @endif

</body>
</html>
