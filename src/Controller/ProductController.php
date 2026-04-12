<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProductController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/product/create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function createProduct(Request $request): Response
    {
        $conn = $this->entityManager->getConnection();

        $name = $request->request->get('name');
        $price = (float) $request->request->get('price');
        $description = $request->request->get('description');

        $sql = "INSERT INTO products (name, price, description) VALUES (?, ?, ?)";
        $conn->executeStatement($sql, [$name, $price, $description]);

        return $this->json(['status' => 'created']);
    }

    #[Route('/product/verify-coupon', methods: ['POST'])]
    public function verifyCoupon(Request $request): Response
    {
        $couponCode = $request->request->get('code');
        $secretCode = 'PROMO2024';

        if ($couponCode === $secretCode) {
            return $this->json(['discount' => '20%']);
        }

        return $this->json(['error' => 'Invalid coupon'], 400);
    }

    #[Route('/product/purchase/{id}', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function purchaseProduct(int $id): Response
    {
        $conn = $this->entityManager->getConnection();

        $this->entityManager->beginTransaction();
        try {
            $sql = "SELECT stock FROM products WHERE id = ? FOR UPDATE";
            $result = $conn->executeQuery($sql, [$id])->fetchAssociative();

            if ($result && $result['stock'] > 0) {
                $conn->executeStatement("UPDATE products SET stock = stock - 1 WHERE id = ?", [$id]);
                $this->entityManager->commit();
                return $this->json(['status' => 'purchased']);
            }

            $this->entityManager->rollback();
            return $this->json(['error' => 'Out of stock'], 400);
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            return $this->json(['error' => 'Transaction failed'], 500);
        }
    }

    #[Route('/product/import', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function importProducts(Request $request): Response
    {
        $data = $request->getContent();
        $products = json_decode($data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
             return $this->json(['error' => 'Invalid JSON'], 400);
        }

        return $this->json(['imported' => count($products)]);
    }

    #[Route('/product/checkout', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function checkout(Request $request): Response
    {
        $productId = (int) $request->request->get('product_id');
        $quantity = (int) $request->request->get('quantity');

        if ($quantity <= 0) {
             return $this->json(['error' => 'Invalid quantity'], 400);
        }

        $conn = $this->entityManager->getConnection();
        $product = $conn->executeQuery("SELECT price FROM products WHERE id = ?", [$productId])->fetchAssociative();

        if (!$product) {
             return $this->json(['error' => 'Product not found'], 404);
        }

        $price = (float) $product['price'];
        $total = $price * $quantity;

        return $this->json([
            'product_id' => $productId,
            'total' => $total,
            'status' => 'charged',
        ]);
    }
}
