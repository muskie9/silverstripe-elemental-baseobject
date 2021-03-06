<?php

namespace Dynamic\BaseObject\Model;

use DNADesign\Elemental\Forms\TextCheckboxGroupField;
use DNADesign\Elemental\Models\BaseElement;
use Sheadawson\Linkable\Forms\LinkField;
use Sheadawson\Linkable\Models\Link;
use SilverStripe\Assets\Image;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Director;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\Permission;
use SilverStripe\Versioned\Versioned;

/**
 * Class BaseElementObject.
 *
 * @property string $Title
 * @property booelan $ShowTitle
 * @property string $Content
 *
 * @property int $ImageID
 * @property int $ElementLinkID
 *
 * @method Image Image()
 * @method Link ElementLink()
 *
 * @mixin Versioned
 */
class BaseElementObject extends DataObject
{
    /**
     * @var array
     */
    private static $db = array(
        'Title' => 'Varchar(255)',
        'ShowTitle' => 'Boolean',
        'Content' => 'HTMLText',
    );

    /**
     * @var array
     */
    private static $has_one = array(
        'Image' => Image::class,
        'ElementLink' => Link::class,
    );

    /**
     * @var array
     */
    private static $owns = array(
        'Image',
    );

    /**
     * @var string
     */
    private static $default_sort = 'Name ASC';

    /**
     * @var array
     */
    private static $summary_fields = array(
        'Image.CMSThumbnail' => 'Image',
        'Name' => 'Name',
        'Title' => 'Title',
    );

    /**
     * @var array
     */
    private static $searchable_fields = array(
        'Title' => array(
            'title' => 'Headline',
        ),
        'Name' => array(
            'title' => 'Name',
        ),
        'Content' => array(
            'title' => 'Description',
        ),
    );

    /**
     * @var array
     */
    private static $extensions = [
        Versioned::class,
    ];

    /**
     * Adds Publish button.
     *
     * @var bool
     */
    private static $versioned_gridfield_extensions = true;

    /**
     * @var string
     */
    private static $table_name = 'BaseElementObject';

    /**
     * @return FieldList
     *
     * @throws \Exception
     */
    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function ($fields) {
            /** @var FieldList $fields */
            $fields->replaceField(
                'ElementLinkID',
                LinkField::create('ElementLinkID')
                    ->setTitle('Link')
                    ->setDescription('Optional. Add a call to action link.')
            );
            $fields->insertBefore($fields->dataFieldByName('ElementLinkID'), 'Content');

            $fields->removeByName(array(
                'ElementFeaturesID',
                'Sort',
            ));

            // Add a combined field for "Title" and "Displayed" checkbox in a Bootstrap input group
            $fields->removeByName('ShowTitle');
            $fields->replaceField(
                'Title',
                TextCheckboxGroupField::create(
                    TextField::create('Title', _t(BaseElement::class . '.TitleLabel', 'Title (displayed if checked)')),
                    CheckboxField::create('ShowTitle', _t(BaseElement::class . '.ShowTitleLabel', 'Displayed'))
                )->setName('TitleAndDisplayed')
            );
            // $fields->dataFieldByName('Title')->setDescription('Optional. Display a Title with this feature.');

            $image = $fields->dataFieldByName('Image')
                ->setDescription('Optional. Display an image with this feature.')
                ->setFolderName('Uploads/Elements/Objects');
            $fields->insertBefore($image, 'Content');

            $fields->dataFieldByName('Content')
                ->setTitle('Description')
                ->setDescription('Optional. Set a description for this feature.')
                ->setRows(8);
        });

        return parent::getCMSFields();
    }

    /**
     * @return SiteTree|null
     */
    public function getPage()
    {
        $page = Director::get_current_page();
        // because $page can be a SiteTree or Controller
        return $page instanceof SiteTree ? $page : null;
    }

    /**
     * Basic permissions, defaults to page perms where possible.
     *
     * @param \SilverStripe\Security\Member|null $member
     * @return boolean
     */
    public function canView($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }

        if ($page = $this->getPage()) {
            return $page->canView($member);
        }

        return Permission::check('CMS_ACCESS', 'any', $member);
    }

    /**
     * Basic permissions, defaults to page perms where possible.
     *
     * @param \SilverStripe\Security\Member|null $member
     *
     * @return boolean
     */
    public function canEdit($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }

        if ($page = $this->getPage()) {
            return $page->canEdit($member);
        }

        return Permission::check('CMS_ACCESS', 'any', $member);
    }

    /**
     * Basic permissions, defaults to page perms where possible.
     *
     * Uses archive not delete so that current stage is respected i.e if a
     * element is not published, then it can be deleted by someone who doesn't
     * have publishing permissions.
     *
     * @param \SilverStripe\Security\Member|null $member
     *
     * @return boolean
     */
    public function canDelete($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }

        if ($page = $this->getPage()) {
            return $page->canArchive($member);
        }

        return Permission::check('CMS_ACCESS', 'any', $member);
    }

    /**
     * Basic permissions, defaults to page perms where possible.
     *
     * @param \SilverStripe\Security\Member|null $member
     * @param array $context
     *
     * @return boolean
     */
    public function canCreate($member = null, $context = array())
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }

        return Permission::check('CMS_ACCESS', 'any', $member);
    }
}
