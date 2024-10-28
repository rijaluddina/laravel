<?php

namespace App\Services;

use App\Models\ProductTransaction;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\PromoCodeRepositoryInterface;
use App\Repositories\Contracts\ShoeRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    protected $orderRepository;
    protected $shoeRepository;
    protected $categoryRepository;
    protected $promoCodeRepository;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        ShoeRepositoryInterface $shoeRepository,
        CategoryRepositoryInterface $categoryRepository,
        PromoCodeRepositoryInterface $promoCodeRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->shoeRepository = $shoeRepository;
        $this->categoryRepository = $categoryRepository;
        $this->promoCodeRepository = $promoCodeRepository;
    }

    public function beginOrder(array $data)
    {
        $orderData = [
            'shoe_id' => $data['shoe_id'],
            'shoe_size' => $data['shoe_size'],
            'size_id' => $data['size_id'],
        ];

        $this->orderRepository->saveToSession($orderData);
    }

    public function getOrderDetails()
    {
        $orderData = $this->orderRepository->getOrderDataFromSession();
        $shoe = $this->shoeRepository->find($orderData['shoe_id']);

        $quantity = isset($orderData['quantity']) ? $orderData['quantity'] : 1;
        $subtotalAmount = $shoe->price * $quantity; // $subTotalAmount = $shoe->price * $quantity;

        $taxRate = 0.11;
        $totalTax = $subtotalAmount * $taxRate;

        $grandTotalAmount = $subtotalAmount + $totalTax;

        $orderData['subtotal_amount'] = $subtotalAmount;
        $orderData['total_tax'] = $totalTax;
        $orderData['grand_total_amount'] = $grandTotalAmount;

        return compact('orderData', 'shoe');
    }

    public function applyPromoCode(string $code, int $subtotalAmount)
    {
        $promo = $this->promoCodeRepository->findByCode($code);

        if ($promo) {
            $discount = $promo->dicount_amount;
            $grandTotalAmount = $subtotalAmount - $discount;
            $promoCodeId = $promo->id;

            return [
                'discount' => $discount,
                'grand_total_amount' => $grandTotalAmount,
                'promo_code_id' => $promoCodeId
            ];
        }

        return [
            'error' => 'Kode promo tidak tersedia!'
        ];
    }

    public function saveBookingTransaction(array $data)
    {
        $this->orderRepository->saveToSession($data);
    }

    public function updateCustomerData(array $data)
    {
        $this->orderRepository->updateSessionData($data);
    }

    public function paymentConfirm(array $validated)
    {
        $orderData = $this->orderRepository->getOrderDataFromSession();
        $productTransactionId = null;

        try { // closure db transaction
            DB::transaction(function () use ($validated, &$productTransactionId, $orderData) {
                if (isset($validated['proof'])) {
                    $proofPath = $validated['proof']->store('proofs', 'public');
                    $validated['proof'] = $proofPath;
                }

                $validated['name'] = $orderData['name'];
                $validated['email'] = $orderData['email'];
                $validated['phone'] = $orderData['phone'];
                $validated['address'] = $orderData['address'];
                $validated['post_code'] = $orderData['post_code'];
                $validated['city'] = $orderData['city'];

                $validated['quantity'] = $orderData['quantity'];
                $validated['subtotal_amount'] = $orderData['subtotal_amount'];
                $validated['grand_total_amount'] = $orderData['grand_total_amount'];
                $validated['promo_code_id'] = $orderData['promo_code_id'];
                $validated['discount_amount'] = $orderData['total_discount_amount'];
                $validated['shoe_id'] = $orderData['shoe_id'];
                $validated['shoe_size'] = $orderData['shoe_size'];
                $validated['is_paid'] = false;
                $validated['booking_trx_id'] = ProductTransaction::generateUniqueTrxId();

                $newTransaction = $this->orderRepository->createTransaction($validated);
                $productTransactionId = $newTransaction->id;
            });
        } catch (\Exception $e) {
            Log::error('Error in payment confirmation: ' . $e->getMessage());
            session()->flash('error', $e->getMessage());
            return null;
        }

        return $productTransactionId;
    }
}
