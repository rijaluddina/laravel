<?php

namespace App\Livewire;

use App\Models\Shoe;
use Livewire\Component;
use App\Services\OrderService;

class OrderForm extends Component
{
    public Shoe $shoe;
    public $orderData;
    public $subtotalAmount;
    public $promoCode = null;
    public $promoCodeId = null;
    public $quantity = 1;
    public $discount;
    public $grandTotalAmount;
    public $totalDiscountAmount = 0;
    public $name;
    public $email;

    protected $orderService;

    public function boot(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function mount(Shoe $shoe, $orderData)
    {
        $this->shoe = $shoe;
        $this->orderData = $orderData;
        $this->subtotalAmount = $shoe->price;
        $this->grandTotalAmount = $shoe->price;
    }

    public function updatedQuantity()
    {
        $this->validateOnly('quantity', [
            'quantity' => 'required|integer|min:1|max:' . $this->shoe->stock,
        ], [
            'quantity.max' => 'Stock tidak tersedia!',
        ]);

        $this->calculateTotal();
    }

    public function calculateTotal()
    {
        $this->subtotalAmount = $this->shoe->price * $this->quantity;
        $this->grandTotalAmount = $this->subtotalAmount - $this->discount;
    }

    public function incrementQuantity()
    {
        if ($this->quantity < $this->shoe->stock) {
            $this->quantity++;
            $this->calculateTotal();
        }
    }

    public function decrementQuantity()
    {
        if ($this->quantity > 1) {
            $this->quantity--;
            $this->calculateTotal();
        }
    }

    public function applyPromoCode()
    {
        if (!$this->promoCode) {
            $this->resetDiscount();
            return;
        }

        $resutl = $this->orderService->applyPromoCode($this->promoCode, $this->subtotalAmount);

        if (isset($resutl['error'])) {
            session()->flash('error', $resutl['error']);
            $this->resetDiscount();
        } else {
            session()->flash('message');
            $this->discount = $resutl['discount'];
            $this->calculateTotal();
            $this->promoCodeId = $resutl['promo_code_id'];
            $this->totalDiscountAmount = $resutl['discount'];
        }
    }

    public function resetDiscount()
    {
        $this->discount = 0;
        $this->calculateTotal();
        $this->promoCodeId = null;
        $this->totalDiscountAmount = 0;
    }

    public function updatedPromoCode()
    {
        $this->applyPromoCode();
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'quantity' => 'required|integer|min:1|max:' . $this->shoe->stock,
        ];
    }

    public function getherBookingData(array $validated): array
    {
        return [

            'name' => $validated['name'],
            'email' => $validated['email'],
            'grand_total_amount' => $this->grandTotalAmount,
            'subtotal_amount' => $this->subtotalAmount,
            'total_discount_amount' => $this->totalDiscountAmount,
            'discount' => $this->discount,
            'promo_code' => $this->promoCode,
            'promo_code_id' => $this->promoCodeId,
            'quantity' => $this->quantity,
        ];
    }

    public function submit()
    {
        $validated = $this->validate();
        $bookingData = $this->getherBookingData($validated);
        $this->orderService->updateCustomerData($bookingData);

        return redirect()->route('front.customer_data');
    }

    public function render()
    {
        return view('livewire.order-form');
    }
}
