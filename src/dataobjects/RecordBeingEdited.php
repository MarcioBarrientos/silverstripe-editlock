<?php
/**
 * RecordBeingEdited
 *
 * @package silverstripe-editlock
 * @author shea@silverstripe.com.au
 **/
namespace Sheadawson\Editlock\Model;


use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FormField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\Security;

class RecordBeingEdited extends DataObject implements PermissionProvider
{

    private static $singular_name = "Record Being Edited";
    private static $plural_name = "Records Being Edited";

    private static $db = array(
        'RecordClass' => 'Varchar',
        'RecordID' => 'Int',
    );

    private static $has_one = array(
        'Editor' => Member::class
    );

    private static $table_name = 'Editlock_RecordBeingEdited';


    /**
     * Generates the edit lock warning message displayed to the user
     * @return String
     **/
    public function getLockedMessage()
    {
        $editor = $this->Editor();
        $editorString = $editor->getTitle();
        if ($editor->Email) {
            $editorString .= " &lt;<a href='mailto:$editor->Email'>$editor->Email</a>&gt;";
        }
        $message = sprintf(
            _t(__CLASS__ . '.LOCKEDMESSAGE', 'Sorry, this record is currently being edited by %s. To avoid conflicts and data loss, editing will be locked until they are finished.'),
            //FormField::name_to_label($this->RecordClass),
            $editorString
        );

        if ($this->canEditAnyway()) {
            $editAnywayLink = Controller::join_links(Controller::curr()->getRequest()->getURL(), '?editanyway=1');
            $message .= "<span style='float:right'>";
            $message .= sprintf(_t(__CLASS__ .  '.EDITANYWAY', 'I understand the risks, %s edit anyway %s'), "<a href='$editAnywayLink'>", "</a>");
            $message .= "</span>";
        }

        return $message;
    }


    /**
     * Checks to see if the current user can and edit the record anyway
     * @return Boolean
     **/
    public function canEditAnyway()
    {
        return Permission::check('RECORDBEINGEDITED_EDITANYWAY') ? true : false;
    }


    /**
     * Checks to see if the current user can and has elected to edit the record anyway
     * @return Boolean
     **/
    public function isEditingAnyway()
    {
        if (!$this->canEditAnyway()) {
            return false;
        }

        $request = Injector::inst()->get(HTTPRequest::class);
        $session = $request->getSession();

        $sessionVar = 'EditAnyway_' . Security::getCurrentUser()->ID . '_' . $this->ID;
        if (Controller::curr()->getRequest()->getVar('editanyway') == '1') {
            $session->set($sessionVar, true);

            return true;
        }
        return $session->get($sessionVar) ? true : false;
    }



    /**
     * @return Array
     **/
    public function providePermissions()
    {
        return array(
            'RECORDBEINGEDITED_EDITANYWAY' => array(
                'name' =>_t( __CLASS__ . '.PERMISSION_EDITANYWAY_DESCRIPTION', 'Edit a record that another user is editing'),
                'help' =>_t( __CLASS__ . '.PERMISSION_EDITANYWAY_HELP',  'Let\'s the user dismiss the edit lock and warning'),
                'category' => _t(__CLASS__ . '.PERMISSION_EDITANYWAY_CATEGORY', 'Content permissions'),
                'sort' => 100
            )
        );
    }
}
