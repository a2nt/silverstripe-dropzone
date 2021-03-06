<?php

/**
 * Track files as they're uploaded and remove when they've been saved.
 */
class FileAttachmentFieldTrack extends DataObject
{
    private static $db = [
        'ControllerClass' => 'Varchar(60)',
        'RecordID' => 'Int',
        'RecordClass' => 'Varchar(60)',
    ];

    private static $has_one = [
        'File' => 'File',
    ];

    public static function untrack($fileIDs)
    {
        if (!$fileIDs) {
            return;
        }
        $fileIDs = (array) $fileIDs;
        $trackRecords = self::get()->filter(array('FileID' => $fileIDs));
        foreach ($trackRecords as $trackRecord) {
            $trackRecord->delete();
        }
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if (!$this->exists()) {
            // Store record this file was tracked on.
            if (!$this->RecordID && Controller::has_curr()) {
                $controller = Controller::curr();
                $pageRecord = null;
                if ($controller->hasMethod('data')) {
                    // Store page visiting on frontend (ContentController)
                    $pageRecord = $controller->data();
                } elseif ($controller->hasMethod('currentPageID')) {
                    // Store editing page in CMS (LeftAndMain)
                    $id = $controller->currentPageID();
                    $pageRecord = $controller->getRecord($id);
                } elseif ($controller->hasMethod('getRecord')) {
                    $pageRecord = $controller->getRecord();
                }

                if ($pageRecord && $pageRecord instanceof DataObjectInterface) {
                    $this->RecordID = $pageRecord->ID;
                    $this->RecordClass = $pageRecord->ClassName;
                }
            }
        }
    }

    public function setRecord($record)
    {
        $this->RecordID = $record->ID;
        $this->RecordClass = $record->ClassName;
    }

    public function Record()
    {
        if ($this->RecordClass && $this->RecordID) {
            return DataObject::get_one($this->RecordClass, 'ID = '.(int) $this->RecordID);
        }
    }
}
