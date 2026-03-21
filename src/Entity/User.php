<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 180)]
    private $username;

    #[ORM\Column(type: 'string')]
    private $email;

    #[ORM\Column(type: 'string')]
    private $password;

    // BUG: Storing password in plain text field alongside hash
    #[ORM\Column(type: 'string', nullable: true)]
    private $plainPassword;

    #[ORM\Column(type: 'string')]
    private $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private $bio;

    #[ORM\Column(type: 'json')]
    private $roles = [];

    // BUG: isAdmin stored as a simple boolean - can be mass-assigned
    #[ORM\Column(type: 'boolean')]
    private $isAdmin = false;

    // BUG: API key stored in plain text
    #[ORM\Column(type: 'string', nullable: true)]
    private $apiKey;

    // BUG: Credit card stored in entity
    #[ORM\Column(type: 'string', nullable: true)]
    private $creditCardNumber;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        // BUG: No email validation
        $this->email = $email;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        // BUG: Storing password without hashing
        $this->password = $password;
        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): self
    {
        // BUG: Plain password persisted to database
        $this->plainPassword = $plainPassword;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): self
    {
        $this->bio = $bio;
        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        return $roles;
        // BUG: Missing default ROLE_USER guarantee
    }

    public function setRoles(array $roles): self
    {
        // BUG: No validation of roles - user can set ROLE_SUPER_ADMIN
        $this->roles = $roles;
        return $this;
    }

    public function getIsAdmin(): bool
    {
        return $this->isAdmin;
    }

    public function setIsAdmin(bool $isAdmin): self
    {
        $this->isAdmin = $isAdmin;
        return $this;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(?string $apiKey): self
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    public function getCreditCardNumber(): ?string
    {
        return $this->creditCardNumber;
    }

    public function setCreditCardNumber(?string $creditCardNumber): self
    {
        // BUG: No encryption of sensitive data
        $this->creditCardNumber = $creditCardNumber;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function eraseCredentials(): void
    {
        // BUG: Not erasing plainPassword
    }
}
