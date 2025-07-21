<?php

namespace App\Http\Controllers\API\V1\Frontend;

use App\Http\Controllers\Controller;
use App\Models\DeliveryAddress;
use App\Models\Microsite;
use App\Models\MicrositeItemSize;
use App\Models\Order;
use App\Traits\ResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class MicrositeApiController extends Controller
{
    use ResponseTrait;
    public function micrositeSetup(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            // Validate the order
            $order = Order::where('id', $id)
                ->where('user_id', auth('api')->id())
                ->where('campaign_type', 'microsite')
                ->first();

            if (!$order) {
                return $this->sendError('Campaign not found or not accessible', 'Invalid campaign ID', 404);
            }

            // Validate request data
            $request->validate([
                'products' => ['required', 'array', 'min:1'],
                'products.*.order_item_id' => ['required', 'exists:order_items,id,order_id,' . $id],
                'products.*.ask_size' => ['required', 'in:yes,no'],
                'products.*.input_type' => ['required_if:products.*.ask_size,yes', 'nullable', 'in:plain_text,options'],
                'products.*.options' => ['required_if:products.*.input_type,options', 'nullable', 'array', 'min:1'],
                'products.*.options.*' => ['string', 'max:255']
            ], [
                'products.*.order_item_id.exists' => 'One or more order item IDs are invalid for this order.',
                'products.*.input_type.required_if' => 'Input type is required when ask_size is yes for product :index.',
                'products.*.options.required_if' => 'Options are required when input_type is options for product :index.',
                'products.*.options.min' => 'Options array must contain at least one item when input_type is options for product :index.'
            ]);

            // Update or create microsite records
            foreach ($request->products as $product) {
                $micrositeData = [
                    'ask_size' => $product['ask_size'],
                    'input_type' => $product['ask_size'] === 'yes' ? $product['input_type'] : null,
                    'options' => $product['ask_size'] === 'yes' && $product['input_type'] === 'options' ? $product['options'] : null
                ];

                Microsite::updateOrCreate(
                    [
                        'order_id' => $order->id,
                        'order_item_id' => $product['order_item_id']
                    ],
                    $micrositeData
                );
            }

            DB::commit();

            // Load updated order with microsites for response
            $order->load([
                'microsites' => function ($query) {
                    $query->select([
                        'id',
                        'order_id',
                        'order_item_id',
                        'ask_size',
                        'input_type',
                        'options'
                    ]);
                }
            ]);

            $responseData = $order->toArray();
            $responseData['price'] = number_format($order->price_in_currency, 2) . ' ' . $order->user_currency;
            $responseData['quantity'] = $order->number_of_boxes;
            $responseData['due_date'] = \Carbon\Carbon::parse($order->created_at)->addDays(14)->format('Y-m-d');

            return $this->sendResponse($responseData, 'Campaign setup completed successfully');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to set up campaign for ID ' . $id . ': ' . $e->getMessage());
            return $this->sendError($e->getMessage(), 'Something went wrong', 500);
        }
    }



    /*============= Recipient Page ==============*/
    public function recipientPage($slug)
    {
        try {
            $order = Order::where('slug', $slug)
                ->where('campaign_type', 'microsite')
                ->with([
                    'orderItems.product' => function ($query) {
                        $query->select([
                            'id',
                            'name',
                            'thumbnail',
                            'slug'
                        ]);
                    },
                    'giftBox:id,name,giftle_branded_price,custom_branding_price,plain_price',
                    'deliveryAddresses:id,order_id,recipient_name,email,phone,address_line_1,address_line_2,address_line_3,postal_code,post_town',
                    'billingAddresses:id,order_id,biller_name,email,phone,address_line_1,address_line_2,address_line_3,postal_code,post_town',
                    'microsites' => function ($query) {
                        $query->select([
                            'id',
                            'order_id',
                            'order_item_id',
                            'ask_size',
                            'input_type',
                            'options'
                        ]);
                    }
                ])
                ->first();

            if (!$order) {
                return $this->sendError('Campaign not found', 'Invalid campaign slug', 404);
            }

            // Prepare response data with formatted price, due_date, and thumbnail_url
            $responseData = $order->toArray();
            $responseData['price'] = number_format($order->price_in_currency, 2) . ' ' . $order->user_currency;
            $responseData['quantity'] = $order->number_of_boxes;
            $responseData['due_date'] = \Carbon\Carbon::parse($order->created_at)->addDays(14)->format('Y-m-d');

            // Merge microsite data into order_items and add thumbnail_url
            $responseData['order_items'] = collect($responseData['order_items'])->map(function ($item) use ($order) {
                // Add thumbnail_url to product
                $item['product']['thumbnail_url'] = $item['product']['thumbnail'] ? asset($item['product']['thumbnail']) : null;

                // Find corresponding microsite data
                $microsite = collect($order->microsites)->firstWhere('order_item_id', $item['id']);
                $item['microsite'] = $microsite ? [
                    'ask_size' => $microsite['ask_size'],
                    'input_type' => $microsite['input_type'],
                    'options' => $microsite['options']
                ] : null;

                return $item;
            })->toArray();

            // Remove top-level microsites from response
            unset($responseData['microsites']);

            return $this->sendResponse($responseData, 'Campaign details retrieved successfully');
        } catch (Exception $e) {
            Log::error('Failed to retrieve campaign details for slug ' . $slug . ': ' . $e->getMessage());
            return $this->sendError($e->getMessage(), 'Something went wrong', 500);
        }
    }



    /*==============  Store Recipient Data ===============*/
    public function storeRecipientData(Request $request, $slug)
    {
        try {
            DB::beginTransaction();

            // Validate the order
            $order = Order::where('slug', $slug)
                ->where('campaign_type', 'microsite')
                ->with(['microsites' => function ($query) {
                    $query->select(['id', 'order_id', 'order_item_id', 'ask_size', 'input_type', 'options']);
                }])
                ->first();

            if (!$order) {
                return $this->sendError('Campaign not found', 'Invalid campaign slug', 404);
            }

            // Check delivery address limit
            if ($request->has('delivery_address')) {
                $currentAddressCount = DeliveryAddress::where('order_id', $order->id)->count();
                if ($currentAddressCount >= $order->number_of_boxes) {
                    return $this->sendError(
                        'Delivery address limit reached',
                        "This order allows a maximum of {$order->number_of_boxes} response.",
                        403
                    );
                }
            }

            // Get order items requiring size input
            $sizeRequiredItems = collect($order->microsites)->where('ask_size', 'yes')->pluck('order_item_id')->toArray();

            // Validate request data
            $request->validate([
                'delivery_address' => ['nullable', 'array'],
                'delivery_address.recipient_name' => ['required_if:delivery_address,*,string', 'max:255'],
                'delivery_address.email' => [
                    'required_if:delivery_address,*,email',
                    'max:255',
                    Rule::unique('delivery_addresses', 'email')
                ],
                'delivery_address.phone' => ['required_if:delivery_address,*,string', 'max:20'],
                'delivery_address.address_line_1' => ['required_if:delivery_address,*,string', 'max:255'],
                'delivery_address.address_line_2' => ['nullable', 'string', 'max:255'],
                'delivery_address.address_line_3' => ['nullable', 'string', 'max:255'],
                'delivery_address.postal_code' => ['required_if:delivery_address,*,string', 'max:20'],
                'delivery_address.post_town' => ['required_if:delivery_address,*,string', 'max:255'],
                'sizes' => ['required_if:sizeRequiredItems,*,array', 'min:1', function ($attribute, $value, $fail) use ($sizeRequiredItems) {
                    $providedIds = collect($value)->pluck('order_item_id')->toArray();
                    $missingIds = array_diff($sizeRequiredItems, $providedIds);
                    if (!empty($missingIds)) {
                        $fail('Size data missing for order item IDs: ' . implode(', ', $missingIds));
                    }
                }],
                'sizes.*.order_item_id' => ['required', 'exists:order_items,id,order_id,' . $order->id, function ($attribute, $value, $fail) use ($sizeRequiredItems) {
                    if (!in_array($value, $sizeRequiredItems)) {
                        $fail("Size provided for order_item_id {$value} which does not require size input.");
                    }
                }],
                'sizes.*.size' => ['required', 'string', 'max:255', function ($attribute, $value, $fail) use ($order) {
                    $index = explode('.', $attribute)[1];
                    $orderItemId = request()->input("sizes.{$index}.order_item_id");
                    $microsite = collect($order->microsites)->firstWhere('order_item_id', $orderItemId);
                    if ($microsite && $microsite['input_type'] === 'options' && !in_array($value, $microsite['options'] ?? [])) {
                        $fail("Size '{$value}' is not a valid option for order_item_id {$orderItemId}.");
                    }
                }],
            ]);

            // Create delivery address if provided
            $deliveryAddress = null;
            if ($request->has('delivery_address')) {
                $deliveryAddress = DeliveryAddress::create([
                    'order_id' => $order->id,
                    'recipient_name' => $request->delivery_address['recipient_name'],
                    'email' => $request->delivery_address['email'],
                    'phone' => $request->delivery_address['phone'],
                    'address_line_1' => $request->delivery_address['address_line_1'],
                    'address_line_2' => $request->delivery_address['address_line_2'],
                    'address_line_3' => $request->delivery_address['address_line_3'],
                    'postal_code' => $request->delivery_address['postal_code'],
                    'post_town' => $request->delivery_address['post_town'],
                ]);
            }

            // Store size selections
            foreach ($request->sizes as $sizeData) {
                MicrositeItemSize::create([
                    'delivery_address_id' => $deliveryAddress ? $deliveryAddress->id : null,
                    'order_id' => $order->id,
                    'order_item_id' => $sizeData['order_item_id'],
                    'size' => $sizeData['size'],
                ]);
            }

            DB::commit();

            // Load updated order with relationships for response
            $order->load([
                'orderItems.product' => function ($query) {
                    $query->select(['id', 'name', 'thumbnail', 'slug']);
                },
                'giftBox:id,name,giftle_branded_price,custom_branding_price,plain_price',
                'deliveryAddresses:id,order_id,recipient_name,email,phone,address_line_1,address_line_2,address_line_3,postal_code,post_town',
                'billingAddresses:id,order_id,biller_name,email,phone,address_line_1,address_line_2,address_line_3,postal_code,post_town',
                'microsites' => function ($query) {
                    $query->select(['id', 'order_id', 'order_item_id', 'ask_size', 'input_type', 'options']);
                },
                'deliveryAddresses.micrositeItemSizes' => function ($query) {
                    $query->select(['id', 'delivery_address_id', 'order_id', 'order_item_id', 'size']);
                }
            ]);

            // Prepare response data
            $responseData = $order->toArray();
            $responseData['price'] = number_format($order->price_in_currency, 2) . ' ' . $order->user_currency;
            $responseData['quantity'] = $order->number_of_boxes;
            $responseData['due_date'] = \Carbon\Carbon::parse($order->created_at)->addDays(14)->format('Y-m-d');

            // Merge microsite data into order_items and add thumbnail_url
            $responseData['order_items'] = collect($responseData['order_items'])->map(function ($item) use ($order) {
                // Add thumbnail_url to product
                $item['product']['thumbnail_url'] = $item['product']['thumbnail'] ? asset($item['product']['thumbnail']) : null;

                // Find corresponding microsite data
                $microsite = collect($order->microsites)->firstWhere('order_item_id', $item['id']);
                $item['microsite'] = $microsite ? [
                    'ask_size' => $microsite['ask_size'],
                    'input_type' => $microsite['input_type'],
                    'options' => $microsite['options']
                ] : null;

                // Add size selections for this order item
                $item['selected_sizes'] = collect($order->deliveryAddresses)
                    ->flatMap(function ($address) {
                        return collect($address['microsite_item_sizes'])->map(function ($size) use ($address) {
                            return [
                                'delivery_address_id' => $address['id'],
                                'order_item_id' => $size['order_item_id'],
                                'size' => $size['size']
                            ];
                        });
                    })
                    ->where('order_item_id', $item['id'])
                    ->values()
                    ->toArray();

                return $item;
            })->toArray();

            // Remove top-level microsites from response
            unset($responseData['microsites']);

            return $this->sendResponse($responseData, 'Recipient data stored successfully');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to store recipient data for slug ' . $slug . ': ' . $e->getMessage());
            return $this->sendError($e->getMessage(), 'Something went wrong', 500);
        }
    }



    public function viewRecipientResponses(Request $request, $orderId)
    {
        try {
            // Validate the order and user
            $order = Order::where('id', $orderId)
                ->where('user_id', auth('api')->id())
                ->where('campaign_type', 'microsite')
                ->with([
                    'orderItems.product' => function ($query) {
                        $query->select(['id', 'name', 'thumbnail', 'slug']);
                    },
                    'giftBox:id,name,giftle_branded_price,custom_branding_price,plain_price',
                    'deliveryAddresses:id,order_id,recipient_name,email,phone,address_line_1,address_line_2,address_line_3,postal_code,post_town',
                    'billingAddresses:id,order_id,biller_name,email,phone,address_line_1,address_line_2,address_line_3,postal_code,post_town',
                    'microsites' => function ($query) {
                        $query->select(['id', 'order_id', 'order_item_id', 'ask_size', 'input_type', 'options']);
                    },
                    'deliveryAddresses.micrositeItemSizes' => function ($query) {
                        $query->select(['id', 'delivery_address_id', 'order_id', 'order_item_id', 'size']);
                    }
                ])
                ->first();

            if (!$order) {
                return $this->sendError('Order not found or not accessible', 'Invalid order ID or unauthorized access', 404);
            }

            // Prepare response data
            $responseData = $order->toArray();
            $responseData['price'] = number_format($order->price_in_currency, 2) . ' ' . $order->user_currency;
            $responseData['quantity'] = $order->number_of_boxes;
            $responseData['due_date'] = \Carbon\Carbon::parse($order->created_at)->addDays(14)->format('Y-m-d');

            // Merge microsite data and size selections into order_items
            $responseData['order_items'] = collect($responseData['order_items'])->map(function ($item) use ($order) {
                // Add thumbnail_url to product
                $item['product']['thumbnail_url'] = $item['product']['thumbnail'] ? asset($item['product']['thumbnail']) : null;

                // Find corresponding microsite data
                $microsite = collect($order->microsites)->firstWhere('order_item_id', $item['id']);
                $item['microsite'] = $microsite ? [
                    'ask_size' => $microsite['ask_size'],
                    'input_type' => $microsite['input_type'],
                    'options' => $microsite['options']
                ] : null;

                // Add size selections for this order item using Eloquent relationship
                $item['selected_sizes'] = $order->deliveryAddresses
                    ->flatMap(function ($address) {
                        return $address->micrositeItemSizes; // Access the relationship directly
                    })
                    ->where('order_item_id', $item['id'])
                    ->map(function ($size) use ($order) {
                        $address = $order->deliveryAddresses->firstWhere('id', $size->delivery_address_id);
                        return [
                            'delivery_address_id' => $size->delivery_address_id,
                            'order_item_id' => $size->order_item_id,
                            'size' => $size->size,
                            'recipient_email' => $address ? $address->email : null
                        ];
                    })
                    ->values()
                    ->toArray();

                return $item;
            })->toArray();

            // Remove top-level microsites from response
            unset($responseData['microsites']);

            return $this->sendResponse($responseData, 'Recipient responses retrieved successfully');
        } catch (Exception $e) {
            Log::error('Failed to retrieve recipient responses for order ID ' . $orderId . ': ' . $e->getMessage());
            return $this->sendError($e->getMessage(), 'Something went wrong', 500);
        }
    }
}
