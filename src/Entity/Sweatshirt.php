<?php

namespace App\Entity;

use App\Repository\SweatshirtRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Représente un produit sweat-shirt dans la boutique en ligne Stubborn.
 *
 * @package App\Entity
 */
#[ORM\Entity(repositoryClass: SweatshirtRepository::class)]
class Sweatshirt
{
    /**
     * L'identifiant unique du sweat-shirt.
     *
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Le nom du sweat-shirt.
     *
     * @var string|null
     */
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * Le prix du sweat-shirt en euros.
     *
     * @var float|null
     */
    #[ORM\Column]
    private ?float $price = null;

    /**
     * Indique si le sweat-shirt est mis en avant dans la boutique.
     *
     * @var bool|null
     */
    #[ORM\Column]
    private ?bool $isFeatured = null;

    /**
     * Le nom du fichier image du sweat-shirt.
     *
     * @var string|null
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    /**
     * La collection des entrées de stock pour différentes tailles.
     *
     * @var Collection<int, Stock>
     */
    #[ORM\OneToMany(targetEntity: Stock::class, mappedBy: 'sweatshirt', cascade: ['persist', 'remove'])]
    private Collection $stocks;

    /**
     * Construit une nouvelle instance de Sweatshirt.
     */
    public function __construct()
    {
        $this->stocks = new ArrayCollection();
    }

    /**
     * Récupère l'identifiant unique du sweat-shirt.
     *
     * @return int|null L'identifiant du sweat-shirt
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Récupère le nom du sweat-shirt.
     *
     * @return string|null Le nom du sweat-shirt
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Définit le nom du sweat-shirt.
     *
     * @param string $name Le nom à définir
     * @return static
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Récupère le prix du sweat-shirt.
     *
     * @return float|null Le prix en euros
     */
    public function getPrice(): ?float
    {
        return $this->price;
    }

    /**
     * Définit le prix du sweat-shirt.
     *
     * @param float $price Le prix à définir
     * @return static
     */
    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Vérifie si le sweat-shirt est mis en avant.
     *
     * @return bool|null Vrai si mis en avant, faux sinon
     */
    public function isFeatured(): ?bool
    {
        return $this->isFeatured;
    }

    /**
     * Définit si le sweat-shirt est mis en avant.
     *
     * @param bool $isFeatured Vrai pour mettre en avant, faux sinon
     * @return static
     */
    public function setIsFeatured(bool $isFeatured): static
    {
        $this->isFeatured = $isFeatured;

        return $this;
    }

    /**
     * Récupère le nom du fichier image du sweat-shirt.
     *
     * @return string|null Le nom du fichier image
     */
    public function getImage(): ?string
    {
        return $this->image;
    }

    /**
     * Définit le nom du fichier image du sweat-shirt.
     *
     * @param string|null $image Le nom du fichier image à définir
     * @return static
     */
    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Récupère la collection des entrées de stock.
     *
     * @return Collection<int, Stock> Les entrées de stock
     */
    public function getStocks(): Collection
    {
        return $this->stocks;
    }

    /**
     * Ajoute une entrée de stock au sweat-shirt.
     *
     * @param Stock $stock L'entrée de stock à ajouter
     * @return static
     */
    public function addStock(Stock $stock): static
    {
        if (!$this->stocks->contains($stock)) {
            $this->stocks->add($stock);
            $stock->setSweatshirt($this);
        }

        return $this;
    }

    /**
     * Supprime une entrée de stock du sweat-shirt.
     *
     * @param Stock $stock L'entrée de stock à supprimer
     * @return static
     */
    public function removeStock(Stock $stock): static
    {
        if ($this->stocks->removeElement($stock)) {
            if ($stock->getSweatshirt() === $this) {
                $stock->setSweatshirt(null);
            }
        }

        return $this;
    }
}