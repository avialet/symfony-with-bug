<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    // BUG: Second-order SQL injection via stored data
    #[Route('/product/create', methods: ['POST'])]
    public function createProduct(Request $request): Response
    {
        $conn = $this->getDoctrine()->getManager()->getConnection();

        $name = $request->request->get('name');
        $price = $request->request->get('price');
        $description = $request->request->get('description');

        // BUG: SQL injection via string interpolation
        $sql = "INSERT INTO products (name, price, description) VALUES ('$name', $price, '$description')";
        $conn->executeStatement($sql);

        return $this->json(['status' => 'created']);
    }

    // BUG: Type juggling vulnerability
    #[Route('/product/verify-coupon', methods: ['POST'])]
    public function verifyCoupon(Request $request): Response
    {
        $couponCode = $request->request->get('code');
        $secretCode = 0;

        // BUG: Loose comparison - "any_string" == 0 is true in PHP
        if ($couponCode == $secretCode) {
            return $this->json(['discount' => '100%']);
        }

        return $this->json(['error' => 'Invalid coupon']);
    }

    // BUG: Race condition in stock management
    #[Route('/product/purchase/{id}', methods: ['POST'])]
    public function purchaseProduct(int $id, Request $request): Response
    {
        $conn = $this->getDoctrine()->getManager()->getConnection();

        // BUG: TOCTOU race condition - check and update are not atomic
        $result = $conn->executeQuery("SELECT stock FROM products WHERE id = $id")->fetchAssociative();

        if ($result['stock'] > 0) {
            // Another request could decrement stock between check and update
            sleep(1); // Simulates processing delay making race condition more likely
            $conn->executeStatement("UPDATE products SET stock = stock - 1 WHERE id = $id");
            return $this->json(['status' => 'purchased']);
        }

        return $this->json(['error' => 'Out of stock'], 400);
    }

    // BUG: Insecure deserialization
    #[Route('/product/import', methods: ['POST'])]
    public function importProducts(Request $request): Response
    {
        $data = $request->getContent();
        // BUG: unserialize with user-controlled data - Remote Code Execution risk
        $products = unserialize($data);

        return $this->json(['imported' => count($products)]);
    }

    // BUG: Missing CSRF protection + price manipulation
    #[Route('/product/checkout', methods: ['POST'])]
    public function checkout(Request $request): Response
    {
        // BUG: Price comes from client-side, not from database
        $productId = $request->request->get('product_id');
        $price = $request->request->get('price');
        $quantity = $request->request->get('quantity');

        // BUG: No CSRF token validation
        // BUG: Integer overflow possible with large quantity
        $total = $price * $quantity;

        // BUG: Negative price not checked
        return $this->json([
            'product_id' => $productId,
            'total' => $total,
            'status' => 'charged',
        ]);
    }
}
