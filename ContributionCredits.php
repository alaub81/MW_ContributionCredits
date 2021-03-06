<?php


/*

 * Configuration:

 *  LocalSettings.php => $wgContributionCreditsShowUserSignature = true;

 *                          Default: true

 *                          true:   shows user specific user signature (if configured and not empty; else just the username)

 *                          false:  shows only the username instead of the user signature

 */                                                                


class ContributionCredits {

	/**

	 * @param string &$data

	 * @param Skin $skin

	 */

	public static function onSkinAfterContent( &$data, Skin $skin ) {

		global $wgContentNamespaces,

			$wgContributionCreditsHeader,

			$wgContributionCreditsUseRealNames,

			

			// ---- CHANGES

			$wgContributionCreditsExcludedCategories,

			$wgContributionCreditsShowUserSignature;    // NEW

			// ---------------

			

		$title = $skin->getTitle();

		$namespace = $title->getNamespace();

		$request = $skin->getRequest();

		$action = $request->getVal( 'action', 'view' );

		if ( in_array( $namespace, $wgContentNamespaces ) && $action === 'view' ) {

			// If the page is in the list of excluded categories, don't show the credits

			$categories = $title->getParentCategories();

			foreach ( $categories as $key => $value ) {

				$category = str_ireplace( '_', ' ', $key );

				if ( in_array( $category, $wgContributionCreditsExcludedCategories ) ) {

					return;

				}

			}

			$database = wfGetDB( DB_REPLICA );

			$articleID = $title->getArticleID();

			$links = [];

			

			/* // --------------- original

			 * $result = $database->select(

			 * [ 'revision', 'user' ],

			 * [ 'distinct user.user_id', 'user.user_name', 'user.user_real_name' ],

			 * [ 'user.user_id = revision.rev_user', "rev_page = $articleID", 'rev_user > 0', 'rev_deleted = 0' ],

			 * __METHOD__,

			 * [ 'ORDER BY' => 'user.user_name ASC' ]

			 * );

			 */ // ---------------

			 

			// NEW 

			// perhaps sql a little too generic; check simplification
/*
            $result = $database->select(

                [ 'revision', 'user', 'user_properties' ],

                [ 'DISTINCT user.user_name', 'user.user_real_name', 'user_properties.up_value AS signature' ],

                [ 'revision.rev_id = ' . $articleID, 'revision.rev_user = user.user_id', 'user_properties.up_user = revision.rev_user', 'user_properties.up_property = "nickname"' ],

                __METHOD__,

                [ 'ORDER BY' => 'user.user_name ASC' ]

            );
*/


$result = $database->select(
        [ 'revision', 'user', 'user_properties', 'revision_actor_temp' ],
        [ 'DISTINCT user.user_name', 'user.user_real_name', 'user_properties.up_value AS signature' ],
        [ 'revision.rev_page = ' . $articleID . ' AND revision.rev_user = user.user_id AND user_properties.up_user = revision.rev_user AND user_properties.up_property = "nickname") OR (revision.rev_page = ' . $articleID . ' AND revision_actor_temp.revactor_actor = user.user_id AND revision_actor_temp.revactor_page = ' . $articleID . ' AND user_properties.up_property = "nickname" AND user_properties.up_user = user.user_id' ],
        __METHOD__,
        [ 'ORDER BY' => 'user.user_name ASC' ]
);



            /* // --------------- original

             *  foreach ( $result as $row ) {

             *      if ( $wgContributionCreditsUseRealNames && $row->user_real_name ) {

             *          $link = Linker::userLink( $row->user_id, $row->user_name, $row->user_real_name );

             *      } else {

             *          $link = Linker::userLink( $row->user_id, $row->user_name );

             *      }

             *      $links[] = $link;

             *  }

             */ // ---------------


            // NEW

            foreach ( $result as $row ) {

                if ( $wgContributionCreditsUseRealNames && $row->user_real_name ) {

                    $link = Linker::userLink( $row->user_id, $row->user_name, $row->user_real_name );

                } elseif ( $wgContributionCreditsShowUserSignature && $row->signature ) {

                    $parser = new Parser;

                    $parserOptions = new ParserOptions;

                    $parserOutput = $parser->parse( $row->signature, $skin->getTitle(), $parserOptions );

                    $link = $parserOutput->getText();

                } else {

                    $link = Linker::userLink( $row->user_id, $row->user_name );

                }

                $links[] = $link;

            }

			

			$header = wfMessage( 'contributioncredits-header' );

			if ( $wgContributionCreditsHeader ) {

				$data .= "<h2>$header</h2>";

				$data .= "<ul>";

				foreach ( $links as $link ) {

					$data .= "<li>$link</li>";

				}

				$data .= "</ul>";

			} else {

				$links = implode( ', ', $links );

				$data .= "<p>$header: $links</p>";

			}

		}

	}

}

?>
