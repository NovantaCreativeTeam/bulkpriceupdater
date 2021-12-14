<?php 

namespace Novanta\BulkPriceUpdater\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table()
 * @ORM\Entity()
 * 
 */
class PriceImportLog 
{

    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(name="id_price_import", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="file", type="string", length=255)
     */
    private $file;

    /**
     * @var int
     * @ORM\Column(name="skip_rows", type="integer")
     */
    private $skipRows;

    /**
     * @var string
     * @ORM\Column(name="column_separator", type="string", length=10)
     */
    private $columnSeparator;

    /**
     * @var string
     * @ORM\Column(name="status", type="string", length=10)
     */
    private $status;

    /**
     * @var \DateTime
     * @ORM\Column(name="date_add", type="datetime")
     */
    private $dateAdd;


    /**
     * @return int
     */ 
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int  $id
     * @return void
     */ 
    public function setId(int $id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */ 
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param string  $file
     * @return void
     */ 
    public function setFile(string $file)
    {
        $this->file = $file;
    }

    /**
     * @return int
     */ 
    public function getSkipRows()
    {
        return $this->skipRows;
    }

    /**
     * @param int  $skipRows
     * @return void
     */ 
    public function setSkipRows(int $skipRows)
    {
        $this->skipRows = $skipRows;
    }

    /**
     * @return string
     */ 
    public function getColumnSeparator()
    {
        return $this->columnSeparator;
    }

    /**
     * @param string  $columnSeparator
     * @return void
     */ 
    public function setColumnSeparator(string $columnSeparator)
    {
        $this->columnSeparator = $columnSeparator;
    }

    /**
     * @return string
     */ 
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string  $status
     * @return void
     */ 
    public function setStatus(string $status)
    {
        $this->status = $status;
    }

    /**
     * @return \DateTime
     */ 
    public function getDateAdd()
    {
        return $this->dateAdd;
    }

    /**
     * @param \DateTime  $dateAdd
     * @return void
     */ 
    public function setDateAdd(\DateTime $dateAdd)
    {
        $this->dateAdd = $dateAdd;
    }

    /**
     * Now we tell doctrine that before we persist or update we call the updatedTimestamps() function.
     *
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updatedTimestamps()
    {
        if ($this->getDateAdd() == null) {
            $this->setDateAdd(new \DateTime());
        }
    }
}