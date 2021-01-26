<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/traits.php';  // Generell funktions

// CLASS AlmanacModule
class AlmanacModule extends IPSModule
{
    use ProfileHelper;
    use EventHelper;
    use DebugHelper;

    /**
     * Create.
     */
    public function Create()
    {
        //Never delete this line!
        parent::Create();
        // Public Holidays
        $this->RegisterPropertyString('PublicCountry', 'de');
        $this->RegisterPropertyString('PublicRegion', 'baden-wuerttemberg');
        $this->RegisterAttributeString('PublicURL', 'https://www.schulferien.org/media/ical/deutschland/feiertage_REGION_YEAR.ics');
        // School Vacation
        $this->RegisterPropertyString('SchoolCountry', 'de');
        $this->RegisterPropertyString('SchoolRegion', 'baden-wuerttemberg');
        $this->RegisterPropertyString('SchoolName', 'alle-schulen');
        $this->RegisterAttributeString('SchoolURL', 'https://www.schulferien.org/media/ical/deutschland/ferien_REGION_YEAR.ics');
        // Advanced Settings
        $this->RegisterPropertyBoolean('UpdateHoliday', true);
        $this->RegisterPropertyBoolean('UpdateVacation', true);
        $this->RegisterPropertyBoolean('UpdateDate', true);
        // Register daily update timer
        $this->RegisterTimer('UpdateTimer', 0, 'ALMANAC_Update(' . $this->InstanceID . ');');
    }

    /**
     * Configuration Form.
     *
     * @return JSON configuration string.
     */
    public function GetConfigurationForm()
    {
        // read setup
        $publicCountry = $this->ReadPropertyString('PublicCountry');
        $publicHoliday = $this->ReadPropertyString('PublicRegion');
        // School Vacation
        $schoolCountry = $this->ReadPropertyString('SchoolCountry');
        $schoolRegion = $this->ReadPropertyString('SchoolRegion');
        $schoolName = $this->ReadPropertyString('SchoolName');
        // Debug output
        $this->SendDebug('GetConfigurationForm', 'public country=' . $publicCountry . ', public holiday=' . $publicHoliday .
                        ', school country=' . $schoolCountry . ', school vacation=' . $schoolRegion . ', school name=' . $schoolName, 0);
        // Get Data
        $data = json_decode(file_get_contents(__DIR__ . '/data.json'), true);
        // Get Form
        $form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        // Holiday Regions
        $form['elements'][2]['items'][1]['options'] = $this->GetRegions($data[$publicCountry]);
        // Vacation Regions
        $form['elements'][3]['items'][1]['items'][0]['options'] = $this->GetRegions($data[$schoolCountry]);
        // Schools
        $form['elements'][3]['items'][1]['items'][1]['options'] = $this->GetSchool($data[$schoolCountry], $schoolRegion);
        // Debug output
        //$this->SendDebug('GetConfigurationForm', $form);
        return json_encode($form);
    }

    /**
     * Apply Configuration Changes.
     */
    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
        // Public Holidays
        $publicCountry = $this->ReadPropertyString('PublicCountry');
        $publicRegion = $this->ReadPropertyString('PublicRegion');
        // School Vacation
        $schoolCountry = $this->ReadPropertyString('SchoolCountry');
        $schoolRegion = $this->ReadPropertyString('SchoolRegion');
        $schoolName = $this->ReadPropertyString('SchoolName');
        // Settings
        $isHoliday = $this->ReadPropertyBoolean('UpdateHoliday');
        $isVacation = $this->ReadPropertyBoolean('UpdateVacation');
        $isDate = $this->ReadPropertyBoolean('UpdateDate');
        $this->SendDebug('ApplyChanges', 'public country=' . $publicCountry . ', public holiday=' . $publicRegion .
                        ', school country=' . $schoolCountry . ', school vacation=' . $schoolRegion . ', school name=' . $schoolName .
                        ', updates=' . ($isHoliday ? 'Y' : 'N') . '|' . ($isVacation ? 'Y' : 'N') . '|' . ($isDate ? 'Y' : 'N'), 0);
        // Profile
        $association = [
            [0, 'Nein', 'Close', 0xFF0000],
            [1, 'Ja',   'Ok', 0x00FF00],
        ];
        $this->RegisterProfile(vtBoolean, 'ALMANAC.Question', 'Bulb', '', '', 0, 0, 0, 0, $association);

        // Holiday (Feiertage)
        $this->MaintainVariable('IsHoliday', 'Ist Feiertag?', vtBoolean, 'ALMANAC.Question', 1, $isHoliday);
        $this->MaintainVariable('Holiday', 'Feiertag', vtString, '', 10, $isHoliday);
        // Vacation (Schulferien)
        $this->MaintainVariable('IsVacation', 'Ist Ferienzeit?', vtBoolean, 'ALMANAC.Question', 2, $isVacation);
        $this->MaintainVariable('Vacation', 'Ferien', vtString, '', 20, $isVacation);
        // Date
        $this->MaintainVariable('IsSummer', 'Ist Sommerzeit?', vtBoolean, 'ALMANAC.Question', 3, $isDate);
        $this->MaintainVariable('IsLeapyear', 'Ist Schaltjahr?', vtBoolean, 'ALMANAC.Question', 4, $isDate);
        $this->MaintainVariable('IsWeekend', 'Ist Wochenende?', vtBoolean, 'ALMANAC.Question', 5, $isDate);
        $this->MaintainVariable('WeekNumber', 'Kalenderwoche', vtInteger, '', 30, $isDate);
        $this->MaintainVariable('DaysInMonth', 'Tage im Monat', vtInteger, '', 32, $isDate);
        $this->MaintainVariable('DayOfYear', 'Tag im Jahr', vtInteger, '', 33, $isDate);
        // Working Days (Arbeitstage im Monat)
        $this->MaintainVariable('WorkingDays', 'Arbeitstage im Monat', vtInteger, '', 40, $isDate);
        // Calculate next update interval
        $this->UpdateTimerInterval('UpdateTimer', 0, 0, 1);
    }

    public function RequestAction($ident, $value)
    {
        // Debug output
        $this->SendDebug('RequestAction', $ident . ' => ' . $value);
        // Ident == OnXxxxxYyyyy
        switch ($ident) {
            case 'OnPublicCountry':
                $this->OnPublicCountry($value);
            break;
            case 'OnSchoolCountry':
                $this->OnSchoolCountry($value);
            break;
            case 'OnSchoolRegion':
                $this->OnSchoolRegion($value);
            break;
        }
        return true;
    }

    /**
     * This function will be available automatically after the module is imported with the module control.
     * Using the custom prefix this function will be callable from PHP and JSON-RPC through:.
     *
     * ALMANAC_Update($id);
     */
    public function Update()
    {
        $isHoliday = $this->ReadPropertyBoolean('UpdateHoliday');
        $isVacation = $this->ReadPropertyBoolean('UpdateVacation');
        $isDate = $this->ReadPropertyBoolean('UpdateDate');

        if ($isHoliday || $isVacation || $isDate) {
            $date = json_decode($this->DateInfo(time()), true);
        }

        if ($isHoliday == true) {
            try {
                $this->SetValueString('Holiday', $date['Holiday']);
                $this->SetValueBoolean('IsHoliday', $date['IsHoliday']);
            } catch (Exception $ex) {
                $this->LogMessage($ex->getMessage(), KL_ERROR);
                $this->SendDebug('ERROR HOLIDAY', $ex->getMessage(), 0);
            }
        }
        if ($isVacation == true) {
            try {
                $this->SetValueString('Vacation', $date['SchoolHolidays']);
                $this->SetValueBoolean('IsVacation', $date['IsSchoolHolidays']);
            } catch (Exception $ex) {
                $this->LogMessage($ex->getMessage(), KL_ERROR);
                $this->SendDebug('ERROR VACATION', $ex->getMessage(), 0);
            }
        }
        if ($isDate == true) {
            try {
                $this->SetValueBoolean('IsSummer', $date['IsSummer']);
                $this->SetValueBoolean('IsLeapyear', $date['IsLeapYear']);
                $this->SetValueBoolean('IsWeekend', $date['IsWeekend']);
                $this->SetValueInteger('WeekNumber', $date['WeekNumber']);
                $this->SetValueInteger('DaysInMonth', $date['DaysInMonth']);
                $this->SetValueInteger('DayOfYear', $date['DayOfYear']);
                $this->SetValueInteger('WorkingDays', $date['WorkingDays']);
            } catch (Exception $ex) {
                $this->LogMessage($ex->getMessage(), KL_ERROR);
                $this->SendDebug('ERROR DATE', $ex->getMessage(), 0);
            }
        }

        // calculate next update interval
        $this->UpdateTimerInterval('UpdateTimer', 0, 0, 1);
    }

    /**
     * This function will be available automatically after the module is imported with the module control.
     * Using the custom prefix this function will be callable from PHP and JSON-RPC through:.
     *
     * ALMANAC_DateInfo($id, $ts);
     *
     * @param int $ts Timestamp of the actuale date
     *
     * @return string all extracted infomation about the passed date as json
     */
    public function DateInfo(int $ts): string
    {
        $this->SendDebug('DATE: ', date('d.m.Y', $ts));
        // Output array
        $date = [];

        // simple date infos
        $date['IsSummer'] = boolval(date('I', $ts));
        $date['IsLeapYear'] = boolval(date('L', $ts));
        $date['IsWeekend'] = boolval(date('N', $ts) > 5);
        $date['WeekNumber'] = idate('W', $ts);
        $date['DaysInMonth'] = idate('t', $ts);
        $date['DayOfYear'] = idate('z', $ts) + 1; // idate('z') is zero based

        // get holiday data
        $region = $this->ReadPropertyString('PublicRegion');
        $url = $this->ReadAttributeString('PublicURL');
        $year = date('Y', $ts);
        // prepeare iCal-URL
        $link = str_replace('REGION', $region, $url);
        $link = str_replace('YEAR', $year, $link);
        $data = $this->ExtractDates($link);

        // working days
        $fdm = date('Ym01', $ts);
        $ldm = date('Ymt', $ts);
        $nwd = 0;
        for ($day = $fdm; $day <= $ldm; $day++) {
            // Minus Weekends
            if (date('N', strtotime(strval($day))) > 5) {
                $nwd++;
            }
            // Minus Holidays
            else {
                foreach ($data as $entry) {
                    if ($entry['start'] == $day) {
                        $nwd++;
                        break;
                    }
                }
            }
        }
        $date['WorkingDays'] = $date['DaysInMonth'] - $nwd;

        // check holiday
        $isHoliday = 'Kein Feiertag';
        $now = date('Ymd', $ts) . "\n";
        foreach ($data as $entry) {
            if (($now >= $entry['start']) && ($now <= $entry['end'])) {
                $isHoliday = $entry['name'];
                $this->SendDebug('HOLIDAY: ', $isHoliday, 0);
                break;
            }
        }
        $date['Holiday'] = $isHoliday;
        $date['IsHoliday'] = ($isHoliday == 'Kein Feiertag') ? false : true;
        // no data, no info
        if (empty($data)) {
            $date['Holiday'] = 'Feiertag nicht ermittelbar';
            $date['IsHoliday'] = false;
        }
        // get vication data
        $region = $this->ReadPropertyString('SchoolRegion');
        $school = $this->ReadPropertyString('SchoolName');
        $url = $this->ReadAttributeString('SchoolURL');
        // check vication
        if ((int) date('md', $ts) < 110) {
            $year = date('Y', $ts) - 1;
            $link = str_replace('REGION', $region, $url);
            $link = str_replace('SCHOOL', $school, $link);
            $link = str_replace('YEAR', $year, $link);
            $data0 = $this->ExtractDates($link);
        } else {
            $data0 = [];
        }
        $year = date('Y', $ts);
        $link = str_replace('REGION', $region, $url);
        $link = str_replace('SCHOOL', $school, $link);
        $link = str_replace('YEAR', $year, $link);
        $data1 = $this->ExtractDates($link);
        $data = array_merge($data0, $data1);
        $isVacation = 'Keine Ferien';
        foreach ($data as $entry) {
            if (($now >= $entry['start']) && ($now <= $entry['end'])) {
                $isVacation = explode(' ', $entry['name'])[0];
                $this->SendDebug('VACATION: ', $isVacation, 0);
                break;
            }
        }
        $date['SchoolHolidays'] = $isVacation;
        $date['IsSchoolHolidays'] = ($isVacation == 'Keine Ferien') ? false : true;
        // no data, no info
        if (empty($data)) {
            $date['SchoolHolidays'] = 'Ferien nicht ermittelbar';
            $date['IsSchoolHolidays'] = false;
        }
        // dump result
        $this->SendDebug('DATA: ', $date, 0);
        // return date info as json
        return json_encode($date);
    }

    /**
     * User has selected a new country.
     *
     * @param string $cid Country ID.
     */
    protected function OnPublicCountry($cid)
    {
        // Get Data
        $data = json_decode(file_get_contents(__DIR__ . '/data.json'), true);

        // URL
        $this->WriteAttributeString('PublicURL', $data[$cid][0]['holiday']);
        // Region Options
        $this->UpdateFormField('PublicRegion', 'value', $data[$cid][0]['regions'][0]['ident']);
        $this->UpdateFormField('PublicRegion', 'options', json_encode($this->GetRegions($data[$cid])));
    }

    /**
     * User has selected a new country.
     *
     * @param string $cid Country ID.
     */
    protected function OnSchoolCountry($cid)
    {
        // Get Data
        $data = json_decode(file_get_contents(__DIR__ . '/data.json'), true);

        // URL
        $this->WriteAttributeString('SchoolURL', $data[$cid][0]['vacation']);
        // Region Options
        $region = $data[$cid][0]['regions'][0]['ident'];
        $this->SendDebug('DATA: ', $region, 0);
        $this->UpdateFormField('SchoolRegion', 'value', $region);
        $this->UpdateFormField('SchoolRegion', 'options', json_encode($this->GetRegions($data[$cid])));
        // School Options
        $this->UpdateFormField('SchoolName', 'value', $data[$cid][0]['regions'][0]['schools'][0]['ident']);
        $this->UpdateFormField('SchoolName', 'options', json_encode($this->GetSchool($data[$cid], $region)));
    }

    /**
     * User has selected a new school region.
     *
     * @param string $region region value.
     */
    protected function OnSchoolRegion($region)
    {
        // Get Data
        $data = json_decode(file_get_contents(__DIR__ . '/data.json'), true);

        // Sorry, find the country for the given region
        foreach ($data as $cid => $countries) {
            foreach ($countries[0]['regions'] as $rid => $regions) {
                if ($regions['ident'] == $region) {
                    // School Options
                    $this->UpdateFormField('SchoolName', 'value', $data[$cid][0]['regions'][$rid]['schools'][0]['ident']);
                    $this->UpdateFormField('SchoolName', 'options', json_encode($this->GetSchool($data[$cid], $region)));
                }
            }
        }
    }

    /**
     * Get and extract dates from iCal format.
     *
     * @param string $Ident Ident of the boolean variable
     * @param bool   $value Value of the boolean variable
     *
     * @return array two-dimensional array, each date in one array
     */
    private function ExtractDates(string $url): array
    {
        // Debug output
        $this->SendDebug('LINK: ', $url, 0);
        // read iCal URL as array
        $ics = @file($url . '?k=LsunrdXh9TIBEFFIT4-NmxQpflV4PYPrbP2NrqZ3SCgiYCvHZo0pHVclzEO30QSqH30SWcMMkL-VxdWsbceDRqad1zTkg9YdiWuUnhNU0Yk');
        // error handling
        if ($ics === false) {
            $this->LogMessage($this->Translate('Could not load iCal data!'), KL_ERROR);
            $this->SendDebug('ExtractDates', 'ERROR LOAD  DATA', 0);
            return [];
        }
        // number of lines
        $count = (count($ics) - 1);
        // daten
        $data = [];
        // loop through lines
        for ($line = 0; $line < $count; $line++) {
            if (strstr($ics[$line], 'SUMMARY:')) {
                $name = trim(substr($ics[$line], 8));
                $pos = strpos($name, '(Bankfeiertag)');
                if ($pos > 0) {
                    $name = substr($name, 0, $pos - 1);
                }
                $start = trim(substr($ics[$line + 1], 19));
                $end = trim(substr($ics[$line + 2], 17));
                $data[] = ['name' => $name, 'start' => $start, 'end' => $end];
            }
        }

        return $data;
    }

    /**
     * Reads the public regions for a given country.
     *
     * @param string $country country data array.
     * @return array Region options array.
     */
    private function GetRegions($country)
    {
        $options = [];
        // Client List
        foreach ($country[0]['regions'] as $rid => $regions) {
            $options[] = ['caption' => $regions['name'], 'value'=> $regions['ident']];
        }
        return $options;
    }

    /**
     * Reads the schools for a given region.
     *
     * @param string $country country data array.
     * @param string $region region ident.
     * @return array School options array.
     */
    private function GetSchool($country, $region)
    {
        $options = [];
        // Client List
        foreach ($country[0]['regions'] as $rid => $regions) {
            if ($regions['ident'] == $region) {
                foreach ($regions['schools'] as $sid => $schools) {
                    $options[] = ['caption' => $schools['name'], 'value'=> $schools['ident']];
                }
                break;
            }
        }
        return $options;
    }

    /**
     * Update a boolean value.
     *
     * @param string $ident Ident of the boolean variable
     * @param bool   $value Value of the boolean variable
     */
    private function SetValueBoolean(string $ident, bool $value)
    {
        $id = $this->GetIDForIdent($ident);
        SetValueBoolean($id, $value);
    }

    /**
     * Update a string value.
     *
     * @param string $ident Ident of the string variable
     * @param string $value Value of the string variable
     */
    private function SetValueString(string $ident, string $value)
    {
        $id = $this->GetIDForIdent($ident);
        SetValueString($id, $value);
    }

    /**
     * Update a integer value.
     *
     * @param string $ident Ident of the integer variable
     * @param int    $value Value of the integer variable
     */
    private function SetValueInteger(string $ident, int $value)
    {
        $id = $this->GetIDForIdent($ident);
        SetValueInteger($id, $value);
    }
}