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
            ['name' => 'Blackbelt', 'price' => 29.90, 'isFeatured' => true, 'image' => 'blackbelt.jpg'],
            ['name' => 'BlueBelt', 'price' => 29.90, 'isFeatured' => false, 'image' => 'bluebelt.jpg'],
            ['name' => 'Street', 'price' => 34.50, 'isFeatured' => false, 'image' => 'street.jpg'],
            ['name' => 'Pokeball', 'price' => 45.00, 'isFeatured' => true, 'image' => 'pokeball.jpg'],
            ['name' => 'PinkLady', 'price' => 29.90, 'isFeatured' => false, 'image' => 'pinklady.jpg'],
            ['name' => 'Snow', 'price' => 32.00, 'isFeatured' => false, 'image' => 'snow.jpg'],
            ['name' => 'Greyback', 'price' => 28.50, 'isFeatured' => false, 'image' => 'greyback.jpg'],
            ['name' => 'BlueCloud', 'price' => 45.00, 'isFeatured' => false, 'image' => 'bluecloud.jpg'],
            ['name' => 'BornInUsa', 'price' => 59.90, 'isFeatured' => true, 'image' => 'borninusa.jpg'],
            ['name' => 'GreenSchool', 'price' => 42.20, 'isFeatured' => false, 'image' => 'greenschool.jpg'],
        ];

        foreach ($sweatshirts as $data) {
            $sweatshirt = new Sweatshirt();
            $sweatshirt->setName($data['name']);
            $sweatshirt->setPrice($data['price']);
            $sweatshirt->setIsFeatured($data['isFeatured']);
            $sweatshirt->setStock(['XS' => 2, 'S' => 2, 'M' => 2, 'L' => 2, 'XL' => 2]);
            $sweatshirt->setImage($data['image']);
            $manager->persist($sweatshirt);
        }

        $manager->flush();
    }
}