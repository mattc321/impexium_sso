<?php
namespace Drupal\impexium_sso\Api\Model\Response;

class ImpexiumUser extends AbstractResponseModel
{
  /** @var string|null */
  protected $firstName;
  /** @var string|null */
  protected $lastName;
  /** @var string|null */
  protected $middleName;
  /** @var string|null */
  protected $preferredFirstName;
  /** @var string|null */
  protected $secondLastName;
  /** @var string|null */
  protected $prefix;
  /** @var string|null */
  protected $suffix;
  /** @var string|null */
  protected $gender;
  /** @var string|null */
  protected $sourceCode;
  /** @var array|null */
  protected $primaryOrganization;
  /** @var array|null */
  protected $committees;
  /** @var array|null */
  protected $securityRoles;
  /** @var array|null */
  protected $user;
  /** @var array|null */
  protected $designationData;
  /** @var array|null */
  protected $relationships;
  /** @var array|null */
  protected $jobRoles;
  /** @var array|null */
  protected $tags;
  /** @var string|int|null */
  protected $preferredCommunicationType;
  /** @var string|null */
  protected $salutation;
  /** @var string|null */
  protected $id;
  /** @var string|null */
  protected $customerType;
  /** @var string|null */
  protected $recordNumber;
  /** @var string|null */
  protected $title;
  /** @var string|null */
  protected $name;
  /** @var array|null */
  protected $addresses;
  /** @var string|null */
  protected $email;
  /** @var string|null */
  protected $imageUri;
  /** @var string|null */
  protected $twitter;
  /** @var string|null */
  protected $linkedIn;
  /** @var array|null */
  protected $emails;
  /** @var array|null */
  protected $phones;
  /** @var string|null */
  protected $webSite;
  /** @var array|null */
  protected $memberships;
  /** @var string|null */
  protected $category;
  /** @var array|null */
  protected $categories;
  /** @var array|null */
  protected $customFields;
  /** @var bool|null */
  protected $showInDirectory;
  /** @var array|null */
  protected $links;
  /** @var string|null */
  protected $oldId;
  /** @var bool|null */
  protected $isDeceased;

  /**
   * @return string|null
   */
  public function getFirstName(): ?string
  {
    return $this->firstName;
  }

  /**
   * @return string|null
   */
  public function getLastName(): ?string
  {
    return $this->lastName;
  }

  /**
   * @return string|null
   */
  public function getMiddleName(): ?string
  {
    return $this->middleName;
  }

  /**
   * @return string|null
   */
  public function getPreferredFirstName(): ?string
  {
    return $this->preferredFirstName;
  }

  /**
   * @return string|null
   */
  public function getSecondLastName(): ?string
  {
    return $this->secondLastName;
  }

  /**
   * @return string|null
   */
  public function getPrefix(): ?string
  {
    return $this->prefix;
  }

  /**
   * @return string|null
   */
  public function getSuffix(): ?string
  {
    return $this->suffix;
  }

  /**
   * @return string|null
   */
  public function getGender(): ?string
  {
    return $this->gender;
  }

  /**
   * @return string|null
   */
  public function getSourceCode(): ?string
  {
    return $this->sourceCode;
  }

  /**
   * @return array|null
   */
  public function getPrimaryOrganization(): ?array
  {
    return $this->primaryOrganization;
  }

  /**
   * @return array|null
   */
  public function getCommittees(): ?array
  {
    return $this->committees;
  }

  /**
   * @return array|null
   */
  public function getSecurityRoles(): ?array
  {
    return $this->securityRoles;
  }

  /**
   * @return array|null
   */
  public function getUser(): ?array
  {
    return $this->user;
  }

  /**
   * @return array|null
   */
  public function getDesignationData(): ?array
  {
    return $this->designationData;
  }

  /**
   * @return array|null
   */
  public function getRelationships(): ?array
  {
    return $this->relationships;
  }

  /**
   * @return array|null
   */
  public function getJobRoles(): ?array
  {
    return $this->jobRoles;
  }

  /**
   * @return array|null
   */
  public function getTags(): ?array
  {
    return $this->tags;
  }

  /**
   * @return int|string|null
   */
  public function getPreferredCommunicationType()
  {
    return $this->preferredCommunicationType;
  }

  /**
   * @return string|null
   */
  public function getSalutation(): ?string
  {
    return $this->salutation;
  }

  /**
   * @return string|null
   */
  public function getId(): ?string
  {
    return $this->id;
  }

  /**
   * @return string|null
   */
  public function getCustomerType(): ?string
  {
    return $this->customerType;
  }

  /**
   * @return string|null
   */
  public function getRecordNumber(): ?string
  {
    return $this->recordNumber;
  }

  /**
   * @return string|null
   */
  public function getTitle(): ?string
  {
    return $this->title;
  }

  /**
   * @return string|null
   */
  public function getName(): ?string
  {
    return $this->name;
  }

  /**
   * @return array|null
   */
  public function getAddresses(): ?array
  {
    return $this->addresses;
  }

  /**
   * @return string|null
   */
  public function getEmail(): ?string
  {
    return $this->email;
  }

  /**
   * @return string|null
   */
  public function getImageUri(): ?string
  {
    return $this->imageUri;
  }

  /**
   * @return string|null
   */
  public function getTwitter(): ?string
  {
    return $this->twitter;
  }

  /**
   * @return string|null
   */
  public function getLinkedIn(): ?string
  {
    return $this->linkedIn;
  }

  /**
   * @return array|null
   */
  public function getEmails(): ?array
  {
    return $this->emails;
  }

  /**
   * @return array|null
   */
  public function getPhones(): ?array
  {
    return $this->phones;
  }

  /**
   * @return string|null
   */
  public function getWebSite(): ?string
  {
    return $this->webSite;
  }

  /**
   * @return array|null
   */
  public function getMemberships(): ?array
  {
    return $this->memberships;
  }

  /**
   * @return string|null
   */
  public function getCategory(): ?string
  {
    return $this->category;
  }

  /**
   * @return array|null
   */
  public function getCategories(): ?array
  {
    return $this->categories;
  }

  /**
   * @return array|null
   */
  public function getCustomFields(): ?array
  {
    return $this->customFields;
  }

  /**
   * @return bool|null
   */
  public function getShowInDirectory(): ?bool
  {
    return $this->showInDirectory;
  }

  /**
   * @return array|null
   */
  public function getLinks(): ?array
  {
    return $this->links;
  }

  /**
   * @return string|null
   */
  public function getOldId(): ?string
  {
    return $this->oldId;
  }

  /**
   * @return bool|null
   */
  public function getIsDeceased(): ?bool
  {
    return $this->isDeceased;
  }

  /**
   * @return array|mixed
   */
  public function getPrimaryAddress()
  {
    //try to get a primary. If not you get the first.
    if (! $this->addresses) {
      return [];
    }

    foreach ($this->addresses as $address) {
      if (isset($address['primary']) && $address['primary'] === true) {
        return $address;
      }
    }

    return $this->addresses[0];

  }

  /**
   * @param string $nameField
   * @return null|array
   */
  public function getCustomField(string $nameField)
  {
    if (! $this->customFields) {
      return null;
    }

    foreach ($this->customFields as $index => $customField) {
      if ($customField['name'] === $nameField) {
        return $this->customFields[$index];
      }
    }

    return null;
  }

}
