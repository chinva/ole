<?php
class PaymentGateway {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function createRazorpayOrder($amount, $currency = 'INR') {
        try {
            if (!RAZORPAY_KEY_ID || !RAZORPAY_KEY_SECRET) {
                return ['success' => false, 'message' => 'Razorpay credentials not configured'];
            }
            
            $orderData = [
                'amount' => $amount * 100, // Convert to paise
                'currency' => $currency,
                'receipt' => 'exam_' . uniqid(),
                'payment_capture' => 1
            ];
            
            $ch = curl_init();
            
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://api.razorpay.com/v1/orders',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($orderData),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Basic ' . base64_encode(RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET)
                ],
                CURLOPT_TIMEOUT => 30
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                return ['success' => false, 'message' => 'Razorpay error: ' . $error];
            }
            
            if ($httpCode !== 200) {
                return ['success' => false, 'message' => 'Razorpay API error: HTTP ' . $httpCode];
            }
            
            $order = json_decode($response, true);
            
            if (isset($order['id'])) {
                return [
                    'success' => true,
                    'order_id' => $order['id'],
                    'amount' => $order['amount'],
                    'currency' => $order['currency']
                ];
            }
            
            return ['success' => false, 'message' => 'Invalid Razorpay response'];
            
        } catch (Exception $e) {
            error_log("Razorpay order creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create Razorpay order'];
        }
    }
    
    public function verifyRazorpayPayment($orderId, $paymentId, $signature) {
        try {
            $expectedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, RAZORPAY_KEY_SECRET);
            
            return hash_equals($expectedSignature, $signature);
            
        } catch (Exception $e) {
            error_log("Razorpay signature verification error: " . $e->getMessage());
            return false;
        }
    }
    
    public function createPayUOrder($amount, $productInfo, $customerName, $customerEmail, $customerPhone) {
        try {
            if (!PAYU_MERCHANT_KEY || !PAYU_MERCHANT_SALT) {
                return ['success' => false, 'message' => 'PayU credentials not configured'];
            }
            
            $txnid = uniqid();
            $hash_string = PAYU_MERCHANT_KEY . '|' . $txnid . '|' . $amount . '|' . $productInfo . '|' . $customerName . '|' . $customerEmail . '|||||||||||' . PAYU_MERCHANT_SALT;
            $hash = strtolower(hash('sha512', $hash_string));
            
            $orderData = [
                'key' => PAYU_MERCHANT_KEY,
                'txnid' => $txnid,
                'amount' => $amount,
                'productinfo' => $productInfo,
                'firstname' => $customerName,
                'email' => $customerEmail,
                'phone' => $customerPhone,
                'surl' => APP_URL . '/api/payment-success.php',
                'furl' => APP_URL . '/api/payment-failure.php',
                'hash' => $hash,
                'service_provider' => 'payu_paisa'
            ];
            
            return [
                'success' => true,
                'order_data' => $orderData,
                'gateway_url' => PAYU_MODE === 'live' 
                    ? 'https://secure.payu.in/_payment' 
                    : 'https://sandboxsecure.payu.in/_payment'
            ];
            
        } catch (Exception $e) {
            error_log("PayU order creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create PayU order'];
        }
    }
    
    public function verifyPayUPayment($responseData) {
        try {
            if (!isset($responseData['key']) || !isset($responseData['txnid']) || !isset($responseData['amount'])) {
                return false;
            }
            
            $status = $responseData['status'];
            $firstname = $responseData['firstname'];
            $email = $responseData['email'];
            $key = $responseData['key'];
            $productinfo = $responseData['productinfo'];
            $amount = $responseData['amount'];
            $txnid = $responseData['txnid'];
            
            $salt = PAYU_MERCHANT_SALT;
            
            $hash_string = $salt . '|' . $status . '|||||||||||' . $email . '|' . $firstname . '|' . $productinfo . '|' . $amount . '|' . $txnid . '|' . $key;
            $hash = strtolower(hash('sha512', $hash_string));
            
            return $hash === $responseData['hash'];
            
        } catch (Exception $e) {
            error_log("PayU verification error: " . $e->getMessage());
            return false;
        }
    }
    
    public function recordPurchase($userId, $examId, $amount, $discount, $finalAmount, $gateway, $orderId, $paymentId = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO purchases 
                (user_id, exam_id, amount, discount, final_amount, payment_gateway, order_id, payment_id, payment_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            
            $stmt->execute([
                $userId,
                $examId,
                $amount,
                $discount,
                $finalAmount,
                $gateway,
                $orderId,
                $paymentId
            ]);
            
            return $this->db->lastInsertId();
            
        } catch (Exception $e) {
            error_log("Purchase recording error: " . $e->getMessage());
            return false;
        }
    }
    
    public function updatePaymentStatus($orderId, $paymentId, $status, $paymentDate = null) {
        try {
            $stmt = $this->db->prepare("
                UPDATE purchases 
                SET payment_status = ?, payment_id = ?, payment_date = ? 
                WHERE order_id = ?
            ");
            
            $stmt->execute([
                $status,
                $paymentId,
                $paymentDate ?? date('Y-m-d H:i:s'),
                $orderId
            ]);
            
            return $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            error_log("Payment status update error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getPurchaseByOrderId($orderId) {
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, e.title as exam_title, e.price as exam_price 
                FROM purchases p 
                JOIN exams e ON p.exam_id = e.id 
                WHERE p.order_id = ?
            ");
            
            $stmt->execute([$orderId]);
            return $stmt->fetch();
            
        } catch (Exception $e) {
            error_log("Purchase retrieval error: " . $e->getMessage());
            return null;
        }
    }
    
    public function hasUserPurchasedExam($userId, $examId) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM purchases 
                WHERE user_id = ? AND exam_id = ? AND payment_status = 'completed'
            ");
            
            $stmt->execute([$userId, $examId]);
            return $stmt->fetchColumn() > 0;
            
        } catch (Exception $e) {
            error_log("Purchase check error: " . $e->getMessage());
            return false;
        }
    }
    
    public function applyCoupon($couponCode, $amount) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM coupons 
                WHERE code = ? 
                AND is_active = 1 
                AND valid_from <= NOW() 
                AND valid_until >= NOW()
                AND (max_usage = 0 OR used_count < max_usage)
            ");
            
            $stmt->execute([$couponCode]);
            $coupon = $stmt->fetch();
            
            if (!$coupon) {
                return ['success' => false, 'message' => 'Invalid or expired coupon'];
            }
            
            if ($amount < $coupon['min_purchase']) {
                return ['success' => false, 'message' => 'Minimum purchase amount not met'];
            }
            
            $discount = ($amount * $coupon['discount_percentage']) / 100;
            
            if ($coupon['max_discount'] && $discount > $coupon['max_discount']) {
                $discount = $coupon['max_discount'];
            }
            
            return [
                'success' => true,
                'discount' => $discount,
                'final_amount' => $amount - $discount,
                'coupon_id' => $coupon['id']
            ];
            
        } catch (Exception $e) {
            error_log("Coupon application error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to apply coupon'];
        }
    }
}
?>