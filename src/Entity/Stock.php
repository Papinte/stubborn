<?php

namespace App\Entity;

use App\Repository\StockRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Représente une entrée de stock pour une taille spécifique d'un sweat-shirt dans l'application Stubborn.
 *
 * @package App\Entity
 */
#[ORM\Entity(repositoryClass: StockRepository::class)]
class Stock
{
    /**
     * L'identifiant unique de l'entrée de stock.
     *
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * La taille du stock (par exemple, XS, S, M, L, XL).
     *
     * @var string|null
     */
    #[ORM\Column(length: 255)]
    private ?string $size = null;

    /**
     * La quantité disponible pour cette taille.
     *
     * @var int|null
     */
    #[ORM\Column]
    private ?int $quantity = null;

    /**
     * Le sweat-shirt associé.
     *
     * @var Sweatshirt|null
     */
    #[ORM\ManyToOne(inversedBy: 'stocks')]
    private ?Sweatshirt $sweatshirt = null;

    /**
     * Récupère l'identifiant unique de l'entrée de stock.
     *
     * @return int|null L'identifiant du stock
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Récupère la taille du stock.
     *
     * @return string|null La taille (par exemple, XS, S, M, L, XL)
     */
    public function getSize(): ?string
    {
        return $this->size;
    }

    /**
     * Définit la taille du stock.
     *
     * @param string $size La taille à définir (par exemple, XS, S, M, L, XL)
     * @return static
     */
    public function setSize(string $size): static
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Récupère la quantité disponible pour cette taille.
     *
     * @return int|null La quantité
     */
    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    /**
     * Définit la quantité disponible pour cette taille.
     *
     * @param int $quantity La quantité à définir
     * @return static
     */
    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Récupère le sweat-shirt associé.
     *
     * @return Sweatshirt|null Le sweat-shirt associé
     */
    public function getSweatshirt(): ?Sweatshirt
    {
        return $this->sweatshirt;
    }

    /**
     * Associe un sweat-shirt à cette entrée de stock.
     *
     * @param Sweatshirt|null $sweatshirt Le sweat-shirt à associer
     * @return static
     */
    public function setSweatshirt(?Sweatshirt $sweatshirt): static
    {
        $this->sweatshirt = $sweatshirt;

        return $this;
    }
}