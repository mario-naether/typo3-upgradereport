<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2013 Steffen Ritter, rs websystems <steffen.ritter@typo3.org>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Class Tx_Smoothmigration_Service_FileLocatorService
 */
class Tx_Smoothmigration_Service_FileLocatorService {

    protected $caseSensitive = TRUE;

	/**
	 * @param string $searchPattern
	 * @param string $haystackFilePath
	 *
	 * @return array
	 */
	public function findLineNumbersOfStringInPhpFile($searchPattern, $haystackFilePath) {
		$positions = array();
		foreach (new SplFileObject($haystackFilePath) as $lineNumber => $lineContent) {
			$matches = array();
            $isMached = preg_match_all('/' . trim($searchPattern, '/') . '/' . ($this->caseSensitive ? 'i' : ''), $lineContent, $matches);
			if ($isMached) {
                foreach($matches[0] as $match) {
                    $positions[] =  array('line' => $lineNumber + 1, 'match' => $match);
                }
			}

		}
		return $positions;
	}

	/**
	 * @param string $fileNamePattern
	 * @param string $searchPattern
	 * @param array $excludedExtensions
	 *
	 * @return Tx_Smoothmigration_Domain_Interface_IssueLocation[]
	 */
	public function searchInExtensions($fileNamePattern, $searchPattern, $excludedExtensions = array()) {
		$locations = array();
		array_push($excludedExtensions, 'smoothmigration');

		$extBasePath = PATH_typo3conf . 'ext';
		foreach (new \DirectoryIterator($extBasePath) as $parentFileInfo) {
			$parentFilename = $parentFileInfo->getFilename();
			if ($parentFilename !== '.' && $parentFilename !== '..' && $parentFileInfo->isDir() && !in_array($parentFilename, $excludedExtensions)) {
				$extensionKeys[] = $parentFilename;
			}
		}
		
		
		foreach ($extensionKeys as $extensionKey) {
			$locations = array_merge($this->searchInExtension($extensionKey, $fileNamePattern, $searchPattern), $locations);
		}
		return $locations;
	}

	/**
	 * @param string $extensionKey
	 * @param string $fileNamePattern
	 * @param string $searchPattern
	 *
	 * @return Tx_Smoothmigration_Domain_Interface_IssueLocation[]
	 *
	 */
	public function searchInExtension($extensionKey, $fileNamePattern, $searchPattern) {
		$pathToExtensionFolder = PATH_typo3conf . 'ext/'.$extensionKey;
		$extensionIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pathToExtensionFolder));
		$regularExpressionIterator = new RegexIterator($extensionIterator, '/' . trim($fileNamePattern, '/') . '/');

		$positions = array();
		foreach ($regularExpressionIterator as $fileInfo) {
			$locations = $this->findLineNumbersOfStringInPhpFile($searchPattern, $fileInfo->getPathname());

			foreach ($locations as $location) {
				$positions[] = new Tx_Smoothmigration_Domain_Model_IssueLocation_File($extensionKey, str_replace(PATH_site, '', $fileInfo->getPathname()), $location['line'], $location['match']);
			}
		}
		return $positions;
	}

    /**
     * @param boolean $caseSensitive
     */
    public function setCaseSensitive($caseSensitive)
    {
        $this->caseSensitive = $caseSensitive;
    }

    /**
     * @return boolean
     */
    public function getCaseSensitive()
    {
        return $this->caseSensitive;
    }

}

?>