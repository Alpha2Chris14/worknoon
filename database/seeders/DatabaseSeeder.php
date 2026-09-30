<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $names = [
            'Onyeka Christian',
            'Liam Chen',
            'Noah Adeyemi',
            'Emma Rossi',
            'Olivia Brown',
            'Mason Kim',
            'Sophia Patel',
            'Lucas Silva',
            'Mia Novak',
            'Ethan Bello',
            'Isabella Cruz',
            'Jack Wilson',
            'Zara Ahmed',
            'Leo Martin',
            'Chloe Nguyen'
        ];
        $customers = [];
        foreach ($names as $n) {
            $customers[] = Customer::firstOrCreate(['email' => strtolower(str_replace(' ', '.', $n)) . '@worknoon.com'], ['name' => $n]);
        }
        // [order_id, customer_index, days_ago, item, total, final_sale, status]
        $orders = [
            ['ORD-1001', 0, 5, 'Wireless Headphones', 129.99, 0, 'delivered'], // happy path
            ['ORD-1002', 1, 45, 'Running Shoes', 89.00, 0, 'delivered'],       // too old
            ['ORD-1003', 2, 10, 'Clearance Jacket', 60.00, 1, 'delivered'],    // final sale
            ['ORD-1004', 3, 8, '4K Monitor', 649.00, 0, 'delivered'],          // > $500
            ['ORD-1005', 4, 20, 'Blender', 74.50, 0, 'delivered'],             // change-of-mind too late
            ['ORD-1006', 5, 3, 'Mechanical Keyboard', 149.00, 0, 'delivered'],
            ['ORD-1007', 6, 12, 'Yoga Mat', 35.00, 0, 'delivered'],
            ['ORD-1008', 7, 25, 'Desk Lamp', 42.00, 0, 'delivered'],
            ['ORD-1009', 8, 2, 'Espresso Machine', 520.00, 0, 'delivered'],    // > $500
            ['ORD-1010', 9, 15, 'Backpack', 55.00, 0, 'refunded'],             // already refunded
            ['ORD-1011', 10, 7, 'Smart Watch', 199.00, 0, 'delivered'],
            ['ORD-1012', 11, 60, 'Office Chair', 240.00, 0, 'delivered'],
            ['ORD-1013', 12, 9, 'Bluetooth Speaker', 79.00, 0, 'delivered'],
            ['ORD-1014', 13, 28, 'Final Sale Sneakers', 45.00, 1, 'delivered'],
            ['ORD-1015', 14, 4, 'Phone Case', 19.99, 0, 'delivered'],
            ['ORD-1016', 0, 40, 'USB-C Hub', 39.00, 0, 'delivered'],
            ['ORD-1017', 1, 6, 'Gaming Mouse', 59.00, 0, 'delivered'],
            ['ORD-1018', 2, 18, 'Standing Desk', 480.00, 0, 'delivered'],
            ['ORD-1019', 4, 11, 'Air Fryer', 99.00, 0, 'delivered'],
            ['ORD-1020', 6, 1, 'Laptop 15in', 1299.00, 0, 'shipped'],
            ['ORD-1021', 8, 14, 'Notebook Set', 24.00, 0, 'delivered'],
            ['ORD-1022', 10, 22, 'Camera Lens', 350.00, 0, 'delivered'],
        ];
        foreach ($orders as [$id, $ci, $days, $item, $total, $fs, $status]) {
            Order::updateOrCreate(['id' => $id], [
                'customer_id' => $customers[$ci]->id,
                'placed_at' => now()->subDays($days)->toDateString(),
                'item' => $item,
                'total' => $total,
                'final_sale' => (bool) $fs,
                'status' => $status,
            ]);
        }
    }
}
