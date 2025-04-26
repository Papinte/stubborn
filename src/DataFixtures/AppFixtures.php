<?php

namespace App\DataFixtures;

use App\Entity\Sweatshirt;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $sweatshirts = [
            ['name' => 'Blackbelt', 'price' => 29.90, 'stock' => ['XS' => 2, 'S' => 2, 'M' => 2, 'L' => 2, 'XL' => 2], 'isFeatured' => true, 'image' => 'blackbelt.jpeg'],
            ['name' => 'BlueBelt', 'price' => 29.90, 'stock' => ['XS' => 2, 'S' => 2, 'M' => 2, 'L' => 2, 'XL' => 2], 'isFeatured' => false, 'image' => 'bluebelt.jepg'],
            ['name' => 'Street', 'price' => 34.50, 'stock' => ['XS' => 2, 'S' => 2, 'M' => 2, 'L' => 2, 'XL' => 2], 'isFeatured' => false, 'image' => 'street.jepg'],
            ['name' => 'Pokeball', 'price' => 45.00, 'stock' => ['XS' => 2, 'S' => 2, 'M' => 2, 'L' => 2, 'XL' => 2], 'isFeatured' => true, 'image' => 'pokeball.jpeg'],
            ['name' => 'PinkLady', 'price' => 29.90, 'stock' => ['XS' => 2, 'S' => 2, 'M' => 2, 'L' => 2, 'XL' => 2], 'isFeatured' => false, 'image' => 'pinklady.jeg'],
            ['name' => 'Snow', 'price' => 32.00, 'stock' => ['XS' => 2, 'S' => 2, 'M' => 2, 'L' => 2, 'XL' => 2], 'isFeatured' => false, 'image' => 'snow.jpeg'],
            ['name' => 'Greyback', 'price' => 28.50, 'stock' => ['XS' => 2, 'S' => 2, 'M' => 2, 'L' => 2, 'XL' => 2], 'isFeatured' => false, 'image' => 'greyback.jpeg'],
            ['name' => 'BlueCloud', 'price' => 45.00, 'stock' => ['XS' => 2, 'S' => 2, 'M' => 2, 'L' => 2, 'XL' => 2], 'isFeatured' => false, 'image' => 'bluecloud.jpeg'],
            ['name' => 'BornInUsa', 'price' => 59.90, 'stock' => ['XS' => 2, 'S' => 2, 'M' => 2, 'L' => 2, 'XL' => 2], 'isFeatured' => true, 'image' => 'borninusa.jpeg'],
            ['name' => 'GreenSchool', 'price' => 42.20, 'stock' => ['XS' => 2, 'S' => 2, 'M' => 2, 'L' => 2, 'XL' => 2], 'isFeatured' => false, 'image' => 'greenschool.jpeg'],
        ];

        foreach ($sweatshirts as $data) {
            $sweatshirt = new Sweatshirt();
            $sweatshirt->setName($data['name']);
            $sweatshirt->setPrice($data['price']);
            $sweatshirt->setStock($data['stock']);
            $sweatshirt->setIsFeatured($data['isFeatured']);
            $sweatshirt->setImage($data['image']);
            $manager->persist($sweatshirt);
        }
    
        $manager->flush();
    }
}