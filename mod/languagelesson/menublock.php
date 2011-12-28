<?php
/**
 * Class definition file for the left-side menu (navigation) block
 * Required by locallib.php
 *
 * @version $Id: menublock.php 677 2011-10-12 18:38:45Z griffisd $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package languagelesson
 **/

class LanguageLessonMenuBlock {

	
	// these vars are instance-dependent, so set them in the constructor
	private $lessonid;
	private $curpageid;
	private $indentpixels;

	// these base texts are instance-independent, but require variables, so set them in the constructor as well
	private $selected;
	private $notselected;
	private $indent_style;


	function __construct($cmid, $lessonid, $curpageid, $indentpixels) {
		global $CFG;
		$this->lessonid = $lessonid;
		$this->curpageid = $curpageid;
		$this->indentpixels = $indentpixels;

		// initialize the base texts for selected and non-selected links
		$this->selected = '<li class="selected"><span %s>%s</span> %s %s</li>';
		$this->notselected = "<li class=\"notselected\"><a href=\"$CFG->wwwroot/mod/"
						  . "languagelesson/view.php?id=$cmid&amp;pageid=%s\""
						  . "class=\"%s\" %s >%s</a>%s %s</li>\n";
		// initialize the base style declaration used in setting indent values
		$this->indent_style = 'style="margin-left:%dpx;"';
	}


	public function printAll($pages, $content) {
		// initialize the nextpageid to the first page in the lesson
		$nextpageid = get_field('languagelesson_pages', 'id', 'prevpageid', 0, 'lessonid', $this->lessonid);
		// now loop until printPage does not return a nextpageid
		// NOTE that this is not just a foreach($pages as $page) because when printPage is called on a branchtable, the pages contained
		// in the branchtable will be handled by that function call, so they should not be touched here at the 0 depth
		while ($nextpageid) {
			list($nextpageid, $content) = $this->printPage($pages[$nextpageid], $content, 0);
		}
		return $content;
	}


	private function printPage($page, $content, $curdepth) {
		global $USER;

		switch ($page->qtype) {

			case LL_CLUSTER:
			case LL_ENDOFCLUSTER:
			case LL_ENDOFBRANCH:
				// nextpageid will be determined outside of this function call, but so that it has a value, set it here
				$nextpageid = null;
				break;

			case LL_BRANCHTABLE:
				list($nextpageid, $content) = $this->printBranchTable($page, $content, $curdepth);
				break;

			default:
				if ($state = languagelesson_get_autograde_state($this->lessonid, $page->id, $USER->id)) {
					if (get_field('languagelesson', 'contextcolors', 'id', $this->lessonid)) {
						// reset the optional second image string
						$img2 = '';
						if ($state == 'correct') {
							$class = 'leftmenu_autograde_correct';
							$img = "<img src=\"{$CFG->wwwroot}".get_string('iconsrccorrect', 'languagelesson')."\"
								width=\"10\" height=\"10\" alt=\"correct\" />";
						} else if ($state == 'incorrect') {
							$class = 'leftmenu_autograde_incorrect';
							$img = "<img src=\"{$CFG->wwwroot}".get_string('iconsrcwrong', 'languagelesson')."\"
								width=\"10\" height=\"10\" alt=\"incorrect\" />";
						} else {
							//it's manually-graded
							$class = 'leftmenu_manualgrade';
							$src = get_string('iconsrcmanual', 'languagelesson');
							$fbsrc = get_string('iconsrcfeedback', 'languagelesson');
							$img = "<img src=\"{$CFG->wwwroot}$src\"
								width=\"10\" height=\"10\" alt=\"manually-graded\" />";
							if ($state == 'feedback') {
								$img2 = "<img src=\"{$CFG->wwwroot}$fbsrc\"
									width=\"15\" height=\"15\" alt=\"manually-graded\" />";
							}
						}
					} else {
						$class = 'leftmenu_attempted';
						$img = '';
					}
				} else {
					//page has not been attempted, so don't mod the style and don't include an image
					$class = 'leftmenu_noattempt';
					$img = '';
				}
			/// print the link based on if it is the current page or not
				if ($page->id == $this->curpageid) { 
					$content .= sprintf($this->selected, sprintf($this->indent_style, $curdepth*$this->indentpixels),
						format_string($page->title,true), $img, ((!empty($img2)) ? $img2 : ''));
				} else {
					$content .= sprintf($this->notselected, $page->id, $class, sprintf($this->indent_style, $curdepth*$this->indentpixels),
						format_string($page->title,true), $img, ((!empty($img2)) ? $img2 : ''));
				}

				// and pull the ID of the next page to print; since this is just a normal sequential page, this will be its nextpageid
				// value
				$nextpageid = $page->nextpageid;
				break;

		} // end switch($page->qtype)

		return array($nextpageid, $content);

	}








	private function printBranchTable($bt, $content, $curdepth) {

		// print the title of the branch table
		if ($bt->id == $this->curpageid) {
			$content .= sprintf($this->selected, sprintf($this->indent_style, $curdepth*$this->indentpixels), format_string($bt->title,true),
					'', '');
		} else {
			$content .= sprintf($this->notselected, $bt->id, '', sprintf($this->indent_style, $curdepth*$this->indentpixels),
					format_string($bt->title, true), '', '');
		}


		// pull the branches belonging to this BT, in order
		$branches = get_records('languagelesson_branches', 'parentid', $bt->id, 'ordering');

		// pull the ID of the next page following the branch table structure; pull this here because if not printing anything in the
		// branch table, still need to return this value
		$lastbranch = end($branches);
		$nextpageid = get_field('languagelesson_pages', 'nextpageid', 'branchid', $lastbranch->id, 'qtype', LL_ENDOFBRANCH);


		// only continue printing branch table contents if the current page is inside the branch table structure, or if the current
		// page is the branch table
		$curpagebranch = get_field('languagelesson_pages', 'branchid', 'id', $this->curpageid);
		if (get_field('languagelesson_branches', 'parentid', 'id', $curpagebranch) != $bt->id
				&& $this->curpageid != $bt->id) {
			return array($nextpageid, $content);
		}


		// print out branch contents as appropriate (branch titles are indented a half-level)
		foreach ($branches as $branchid => $branch) {

			// if the current page is contained in this branch, print its title as selected and print its pages 
			if (get_field('languagelesson_pages', 'branchid', 'id', $this->curpageid) == $branchid) {
				$content .= sprintf($this->selected, sprintf($this->indent_style, ($curdepth+0.5)*$this->indentpixels),
						format_string($branch->title,true), '', '');

				// pull the pages contained in this branch, in sorted order
				$branchpages = get_records('languagelesson_pages', 'branchid', $branchid, 'ordering');
				// increment the depth, as we're now going to print pages inside this BT
				$curdepth++;
				foreach ($branchpages as $page) {
					list($foo, $content) = $this->printPage($page, $content, $curdepth);
				}
				// decrement the depth, as we're now returning to the same level as the BT
				$curdepth--;

			}

			// otherwise, just print the branch title as notselected
			else {
				// set the _GET variable data dependent on the branch's content; if it has content, just print out the firstpage ID as
				// the pageid to view; if it does not, add the error-causing branchnocontent variable
				$repval = (($branch->firstpage) ? $branch->firstpage : "-1&amp;branchnocontent=1");
				$content .= sprintf($this->notselected, $repval, '', sprintf($this->indent_style, ($curdepth+0.5)*$this->indentpixels),
						format_string($branch->title,true), '', '');
			}

		}


		// return the updated contents of the menu block
		return array($nextpageid, $content);

	}




}

?>
