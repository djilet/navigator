<?php

class GeoHelper{
    public static function setCoordsByAddressIfNeed(LocalObject $object)
    {
        if ((!$object->ValidateNotEmpty('Longitude') || !$object->ValidateNotEmpty('Latitude')) &&
            $object->ValidateNotEmpty('Address')){
            $coords = GetCoordsByAddress($object->GetProperty('Address'));
            $object->SetProperty('Longitude', $coords['Longitude']);
            $object->SetProperty('Latitude', $coords['Latitude']);
        }
    }
}