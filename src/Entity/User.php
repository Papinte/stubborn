<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Représente un utilisateur dans l'application Stubborn, gérant l'authentification et les données utilisateur.
 *
 * @package App\Entity
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'Il existe déjà un compte avec cet e-mail.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * L'identifiant unique de l'utilisateur.
     *
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * L'adresse e-mail de l'utilisateur, utilisée comme identifiant unique.
     *
     * @var string|null
     */
    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    /**
     * Les rôles attribués à l'utilisateur.
     *
     * @var list<string>
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * Le mot de passe haché de l'utilisateur.
     *
     * @var string|null
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * Le nom complet de l'utilisateur.
     *
     * @var string|null
     */
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * L'adresse de livraison de l'utilisateur.
     *
     * @var string|null
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $deliveryAddress = null;

    /**
     * Indique si l'e-mail de l'utilisateur est vérifié.
     *
     * @var bool
     */
    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    /**
     * Récupère l'identifiant unique de l'utilisateur.
     *
     * @return int|null L'identifiant de l'utilisateur
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Récupère l'adresse e-mail de l'utilisateur.
     *
     * @return string|null L'adresse e-mail
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Définit l'adresse e-mail de l'utilisateur.
     *
     * @param string $email L'adresse e-mail à définir
     * @return static
     */
    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Récupère l'identifiant de l'utilisateur (e-mail).
     *
     * @return string L'identifiant de l'utilisateur
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * Récupère les rôles attribués à l'utilisateur.
     *
     * @return list<string> Les rôles de l'utilisateur
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    /**
     * Définit les rôles attribués à l'utilisateur.
     *
     * @param list<string> $roles Les rôles à définir
     * @return static
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * Récupère le mot de passe haché de l'utilisateur.
     *
     * @return string|null Le mot de passe haché
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Définit le mot de passe haché de l'utilisateur.
     *
     * @param string $password Le mot de passe haché à définir
     * @return static
     */
    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Récupère le nom complet de l'utilisateur.
     *
     * @return string|null Le nom de l'utilisateur
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Définit le nom complet de l'utilisateur.
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
     * Récupère l'adresse de livraison de l'utilisateur.
     *
     * @return string|null L'adresse de livraison
     */
    public function getDeliveryAddress(): ?string
    {
        return $this->deliveryAddress;
    }

    /**
     * Définit l'adresse de livraison de l'utilisateur.
     *
     * @param string|null $deliveryAddress L'adresse de livraison à définir
     * @return static
     */
    public function setDeliveryAddress(?string $deliveryAddress): static
    {
        $this->deliveryAddress = $deliveryAddress;
        return $this;
    }

    /**
     * Vérifie si l'e-mail de l'utilisateur est vérifié.
     *
     * @return bool Vrai si vérifié, faux sinon
     */
    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    /**
     * Définit si l'e-mail de l'utilisateur est vérifié.
     *
     * @param bool $isVerified Vrai si vérifié, faux sinon
     * @return static
     */
    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    /**
     * Supprime les données sensibles de l'objet utilisateur.
     */
    public function eraseCredentials(): void
    {
        // Nettoyage des données sensibles si besoin
    }
}