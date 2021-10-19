ALTER TABLE `data_profession`
add `ImportID` varchar(50) DEFAULT NULL after `ProfessionID`,
add `AreaWork` text after `Employee`,
add `ProWageLevel` int(10) DEFAULT NULL after `AreaWork`,
add `Books` text after `ProWageLevel`,
add `Films` text after `Books`,
add `Schedule` varchar(255) DEFAULT NULL after `Films`,
add `Operation` varchar(255) DEFAULT NULL after `Schedule`,
add `WantToWork` varchar(255) DEFAULT NULL after `Operation`,
add `WhoToWork` varchar(255) DEFAULT NULL after `WantToWork`;