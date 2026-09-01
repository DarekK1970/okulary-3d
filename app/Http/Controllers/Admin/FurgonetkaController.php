<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderShipment;
use App\Services\FurgonetkaApiService;
use App\Services\FurgonetkaSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class FurgonetkaController extends Controller
{
    public function settings(
        FurgonetkaSettingsService $settings
    ): View {
        return view(
            'admin.shipping.furgonetka-settings',
            [
                'settings' => $settings,
                'clientIdMasked' =>
                    $settings->masked(
                        'client_id'
                    ),
                'clientSecretMasked' =>
                    $settings->masked(
                        'client_secret'
                    ),
                'mapApiKeyMasked' =>
                    $settings->masked(
                        'map_api_key'
                    ),
            ]
        );
    }

    public function updateSettings(
        Request $request,
        FurgonetkaSettingsService $settings
    ): RedirectResponse {
        $validated = $request->validate([
            'enabled' => [
                'nullable',
                'boolean',
            ],
            'client_id' => [
                'nullable',
                'string',
                'max:255',
            ],
            'client_secret' => [
                'nullable',
                'string',
                'max:255',
            ],
            'map_api_key' => [
                'nullable',
                'string',
                'max:512',
            ],

            'sender_name' => [
                'required',
                'string',
                'max:180',
            ],
            'sender_company' => [
                'nullable',
                'string',
                'max:180',
            ],
            'sender_email' => [
                'required',
                'email:rfc',
                'max:255',
            ],
            'sender_phone' => [
                'required',
                'string',
                'max:40',
            ],
            'sender_street' => [
                'required',
                'string',
                'max:255',
            ],
            'sender_city' => [
                'required',
                'string',
                'max:120',
            ],
            'sender_postcode' => [
                'required',
                'string',
                'max:30',
            ],
            'sender_country_code' => [
                'required',
                'string',
                'size:2',
            ],
            'sender_county' => [
                'nullable',
                'string',
                'max:120',
            ],

            'parcel_width_cm' => [
                'required',
                'integer',
                'min:1',
                'max:300',
            ],
            'parcel_height_cm' => [
                'required',
                'integer',
                'min:1',
                'max:300',
            ],
            'parcel_depth_cm' => [
                'required',
                'integer',
                'min:1',
                'max:300',
            ],
            'label_format' => [
                'required',
                Rule::in([
                    'pdf',
                    'zpl',
                    'epl',
                ]),
            ],
            'label_page_format' => [
                'required',
                Rule::in([
                    'a4',
                    'a6',
                ]),
            ],
        ]);

        $settings->set(
            'enabled',
            $request->boolean(
                'enabled'
            ) ? '1' : '0'
        );

        foreach (
            [
                'client_id' => 'client_id',
                'client_secret' =>
                    'client_secret',
                'map_api_key' =>
                    'map_api_key',
            ]
            as $input => $key
        ) {
            if (filled($validated[$input] ?? null)) {
                $settings->set(
                    $key,
                    $validated[$input],
                    true
                );
            }
        }

        $settings->set(
            'sender.name',
            $validated['sender_name']
        );
        $settings->set(
            'sender.company',
            $validated['sender_company']
                ?? ''
        );
        $settings->set(
            'sender.email',
            $validated['sender_email']
        );
        $settings->set(
            'sender.phone',
            $validated['sender_phone']
        );
        $settings->set(
            'sender.street',
            $validated['sender_street']
        );
        $settings->set(
            'sender.city',
            $validated['sender_city']
        );
        $settings->set(
            'sender.postcode',
            $validated['sender_postcode']
        );
        $settings->set(
            'sender.country_code',
            strtoupper(
                $validated[
                    'sender_country_code'
                ]
            )
        );
        $settings->set(
            'sender.county',
            $validated['sender_county']
                ?? ''
        );

        $settings->set(
            'parcel.width_cm',
            (string) $validated[
                'parcel_width_cm'
            ]
        );
        $settings->set(
            'parcel.height_cm',
            (string) $validated[
                'parcel_height_cm'
            ]
        );
        $settings->set(
            'parcel.depth_cm',
            (string) $validated[
                'parcel_depth_cm'
            ]
        );
        $settings->set(
            'label.format',
            $validated['label_format']
        );
        $settings->set(
            'label.page_format',
            $validated[
                'label_page_format'
            ]
        );

        return back()->with(
            'status',
            __('furgonetka.messages.settings_saved')
        );
    }

    public function connect(
        Request $request,
        FurgonetkaApiService $api
    ): RedirectResponse {
        $state = Str::random(48);

        $request->session()->put(
            'furgonetka_oauth_state',
            $state
        );

        return redirect()->away(
            $api->authorizationUrl($state)
        );
    }

    public function callback(
        Request $request,
        FurgonetkaApiService $api
    ): RedirectResponse {
        $request->validate([
            'code' => [
                'required',
                'string',
            ],
            'state' => [
                'required',
                'string',
            ],
        ]);

        $expected = $request
            ->session()
            ->pull(
                'furgonetka_oauth_state'
            );

        abort_unless(
            filled($expected)
            && hash_equals(
                (string) $expected,
                (string) $request->string(
                    'state'
                )
            ),
            403
        );

        $api->exchangeCode(
            (string) $request->string(
                'code'
            )
        );

        return redirect()
            ->route(
                'admin.shipping.furgonetka.settings'
            )
            ->with(
                'status',
                __('furgonetka.messages.connected')
            );
    }

    public function disconnect(
        FurgonetkaSettingsService $settings
    ): RedirectResponse {
        $settings->clearTokens();

        return back()->with(
            'status',
            __('furgonetka.messages.disconnected')
        );
    }

    public function test(
        FurgonetkaApiService $api
    ): RedirectResponse {
        $services = $api->testConnection();

        return back()->with(
            'status',
            __('furgonetka.messages.connection_ok', [
                'count' => count($services),
            ])
        );
    }

    public function orderPage(
        Order $order,
        FurgonetkaSettingsService $settings,
        FurgonetkaApiService $api
    ): View {
        $services = [];

        if (
            $settings->enabled()
            && $settings->connected()
        ) {
            try {
                $services = $api->services();
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return view(
            'admin.shipping.furgonetka-order',
            [
                'order' =>
                    $order->load(
                        'shipments'
                    ),
                'settings' =>
                    $settings,
                'services' =>
                    $services,
            ]
        );
    }

    public function createShipment(
        Request $request,
        Order $order,
        FurgonetkaApiService $api,
        FurgonetkaSettingsService $settings
    ): RedirectResponse {
        abort_unless(
            $settings->enabled()
            && $settings->connected(),
            422
        );

        $validated = $request->validate([
            'service_id' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);

        $result = $api->createPackage(
            $order,
            (int) $validated[
                'service_id'
            ]
        );

        $response = $result['response'];

        $shipment =
            OrderShipment::create([
                'order_id' => $order->id,
                'provider' =>
                    'furgonetka',
                'external_package_id' =>
                    (string) $response[
                        'package_id'
                    ],
                'service_id' =>
                    (int) (
                        $response[
                            'service_id'
                        ]
                        ?? $validated[
                            'service_id'
                        ]
                    ),
                'carrier' =>
                    $response['service']
                    ?? null,
                'state' =>
                    $response['state']
                    ?? 'waiting',
                'label_format' =>
                    $settings
                        ->labelFormat(),
                'label_page_format' =>
                    $settings
                        ->labelPageFormat(),
                'request_snapshot' =>
                    $result['payload'],
                'response_snapshot' =>
                    $response,
            ]);

        return redirect()
            ->route(
                'admin.shipping.furgonetka.order',
                $order
            )
            ->with(
                'status',
                __('furgonetka.messages.package_created', [
                    'id' =>
                        $shipment
                            ->external_package_id,
                ])
            );
    }

    public function orderShipment(
        Order $order,
        OrderShipment $shipment,
        FurgonetkaApiService $api
    ): RedirectResponse {
        $this->assertOrderShipment(
            $order,
            $shipment
        );

        $result = $api->orderPackage(
            $shipment
        );

        $shipment->update([
            'order_command_uuid' =>
                $result['uuid'],
            'state' => 'ordering',
        ]);

        $status =
            $api->waitForOrderCommand(
                $result['uuid']
            );

        $newState =
            $status['status']
            ?? 'ordering';

        $shipment->update([
            'state' => $newState,
            'ordered_at' =>
                in_array(
                    $newState,
                    [
                        'successful',
                        'partial_success',
                    ],
                    true
                )
                    ? now()
                    : null,
            'response_snapshot' =>
                $status,
        ]);

        if (
            in_array(
                $newState,
                [
                    'successful',
                    'partial_success',
                ],
                true
            )
        ) {
            $api->syncShipment(
                $shipment
            );
        }

        return back()->with(
            'status',
            __('furgonetka.messages.package_ordered')
        );
    }

    public function tracking(
        Order $order,
        OrderShipment $shipment,
        FurgonetkaApiService $api
    ): RedirectResponse {
        $this->assertOrderShipment(
            $order,
            $shipment
        );

        $api->syncShipment($shipment);

        return back()->with(
            'status',
            __('furgonetka.messages.tracking_refreshed')
        );
    }

    public function label(
        Order $order,
        OrderShipment $shipment,
        FurgonetkaApiService $api
    ): Response {
        $this->assertOrderShipment(
            $order,
            $shipment
        );

        abort_if(
            blank(
                $shipment
                    ->external_package_id
            ),
            404
        );

        $response = $api->label(
            $shipment
                ->external_package_id
        );

        abort_if(
            $response->status() === 204,
            404,
            __('furgonetka.errors.label_not_ready')
        );

        $contentType =
            $response->header(
                'Content-Type'
            )
            ?: 'application/octet-stream';

        $extension = str_contains(
            $contentType,
            'pdf'
        )
            ? 'pdf'
            : $shipment->label_format;

        return response(
            $response->body(),
            200,
            [
                'Content-Type' =>
                    $contentType,
                'Content-Disposition' =>
                    'attachment; filename="'
                    . 'furgonetka-'
                    . $shipment
                        ->external_package_id
                    . '.'
                    . $extension
                    . '"',
            ]
        );
    }

    private function assertOrderShipment(
        Order $order,
        OrderShipment $shipment
    ): void {
        abort_unless(
            $shipment->order_id
            === $order->id
            && $shipment->provider
                === 'furgonetka',
            404
        );
    }
}
