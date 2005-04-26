/*------------------------------------------------------------------------------

    Copyright (c) 2004 Media Development Loan Fund
 
    This file is part of the LiveSupport project.
    http://livesupport.campware.org/
    To report bugs, send an e-mail to bugs@campware.org
 
    LiveSupport is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.
  
    LiveSupport is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
 
    You should have received a copy of the GNU General Public License
    along with LiveSupport; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 
 
    Author   : $Author: fgerlits $
    Version  : $Revision: 1.2 $
    Location : $Source: /home/paul/cvs2svn-livesupport/newcvsrepo/livesupport/modules/widgets/src/Attic/AdvancedSearchItem.cxx,v $

------------------------------------------------------------------------------*/

/* ============================================================ include files */

#ifdef HAVE_CONFIG_H
#include "configure.h"
#endif

#include <iostream>

#include "LiveSupport/Widgets/WidgetFactory.h"

#include "LiveSupport/Widgets/AdvancedSearchItem.h"


using namespace LiveSupport::Core;
using namespace LiveSupport::Widgets;

/* ===================================================  local data structures */


/* ================================================  local constants & macros */


/* ===============================================  local function prototypes */


/* =============================================================  module code */

/*------------------------------------------------------------------------------
 *  Constructor.
 *----------------------------------------------------------------------------*/
AdvancedSearchItem :: AdvancedSearchItem(bool                        isFirst,
                                         Ptr<ResourceBundle>::Ref    bundle)
                                                                throw ()
          : LocalizedObject(bundle)
{
    try {
        if (!metadataTypes) {
            readMetadataTypes();
        }
        if (!operatorTypes) {
            readOperatorTypes();
        }
    } catch (std::invalid_argument &e) {
        std::cerr << e.what() << std::endl;
        std::exit(1);
    }
    
    Ptr<WidgetFactory>::Ref     wf = WidgetFactory::getInstance();

    Gtk::Label *    searchByLabel;
    try {
        searchByLabel = Gtk::manage(new Gtk::Label(
                                    *getResourceUstring("searchByTextLabel") ));

    } catch (std::invalid_argument &e) {
        std::cerr << e.what() << std::endl;
        std::exit(1);
    }
    
    pack_start(*searchByLabel, Gtk::PACK_SHRINK, 5);

    metadataEntry = Gtk::manage(wf->createComboBoxText());
    MapVector::const_iterator   it;
    for (it = metadataTypes->begin(); it != metadataTypes->end(); ++it) {
        metadataEntry->append_text(it->first);
    }
    metadataEntry->set_active_text(metadataTypes->front().first);
    pack_start(*metadataEntry, Gtk::PACK_EXPAND_WIDGET, 5);

    operatorEntry = Gtk::manage(wf->createComboBoxText());
    for (it = operatorTypes->begin(); it != operatorTypes->end(); ++it) {
        operatorEntry->append_text(it->first);
    }
    operatorEntry->set_active_text(operatorTypes->front().first);
    pack_start(*operatorEntry,  Gtk::PACK_EXPAND_WIDGET, 5);

    valueEntry = Gtk::manage(wf->createEntryBin());
    pack_start(*valueEntry,     Gtk::PACK_EXPAND_WIDGET, 5);
    
    plusButton = Gtk::manage(wf->createButton(WidgetFactory::plusButton));
    pack_start(*plusButton,     Gtk::PACK_SHRINK, 5);
    
    if (!isFirst) {
        closeButton = Gtk::manage(wf->createButton(WidgetFactory::deleteButton));
        closeButton->signal_clicked().connect(sigc::mem_fun(*this, 
                                            &AdvancedSearchItem::destroy_ ));
        pack_start(*closeButton,    Gtk::PACK_SHRINK, 5);
    }
}


/*------------------------------------------------------------------------------
 *  Return the current state of the search fields.
 *----------------------------------------------------------------------------*/
Ptr<SearchCriteria::SearchConditionType>::Ref
AdvancedSearchItem :: getSearchCondition(void)                  throw ()
{
    std::string    metadataName = metadataEntry->get_active_text();
    std::string    metadataKey;
    bool           found = false;
    MapVector::const_iterator   it;
    for (it = metadataTypes->begin(); it != metadataTypes->end(); ++it) {
        if (it->first == metadataName) {
            found = true;
            metadataKey = it->second;
            break;
        }
    }
    if (!found) {
        std::cerr << "unknown metadata type: " << metadataName
                  << std::endl << "(this should never happen)" << std::endl;
        std::exit(1);
    }

    std::string     operatorName = operatorEntry->get_active_text();
    std::string     operatorKey;
    found = false;
    for (it = operatorTypes->begin(); it != operatorTypes->end(); ++it) {
        if (it->first == operatorName) {
            found = true;
            operatorKey = it->second;
            break;
        }
    }
    if (!found) {
        std::cerr << "unknown comparison operator: " << operatorName
                  << std::endl << "(this should never happen)" << std::endl;
        std::exit(1);
    }

    std::string     value = valueEntry->get_text();
    
    Ptr<SearchCriteria::SearchConditionType>::Ref
            condition(new SearchCriteria::SearchConditionType(metadataKey,
                                                              operatorKey,
                                                              value) );
    
    return condition;
}


/*------------------------------------------------------------------------------
 *  Read the localized metadata field names.
 *----------------------------------------------------------------------------*/
void
AdvancedSearchItem :: readMetadataTypes(void) 
                                                throw (std::invalid_argument)
{
    metadataTypes.reset(new MapVector);
    
    metadataTypes->push_back(std::make_pair(
                            *getResourceUstring("genreMetadataDisplay"),
                            *getResourceUstring("genreMetadataSearchKey") ));
    metadataTypes->push_back(std::make_pair(
                            *getResourceUstring("creatorMetadataDisplay"),
                            *getResourceUstring("creatorMetadataSearchKey") ));
    metadataTypes->push_back(std::make_pair(
                            *getResourceUstring("albumMetadataDisplay"),
                            *getResourceUstring("albumMetadataSearchKey") ));
    metadataTypes->push_back(std::make_pair(
                            *getResourceUstring("titleMetadataDisplay"),
                            *getResourceUstring("titleMetadataSearchKey") ));
    metadataTypes->push_back(std::make_pair(
                            *getResourceUstring("lengthMetadataDisplay"),
                            *getResourceUstring("lengthMetadataSearchKey") ));
}


/*------------------------------------------------------------------------------
 *  Read the localized comparison operator names.
 *----------------------------------------------------------------------------*/
void
AdvancedSearchItem :: readOperatorTypes(void) 
                                                throw (std::invalid_argument)
{
    operatorTypes.reset(new MapVector);
    
    operatorTypes->push_back(std::make_pair(
                            *getResourceUstring("partialOperatorDisplay"),
                            *getResourceUstring("partialOperatorSearchKey") ));
    operatorTypes->push_back(std::make_pair(
                            *getResourceUstring("prefixOperatorDisplay"),
                            *getResourceUstring("prefixOperatorSearchKey") ));
    operatorTypes->push_back(std::make_pair(
                            *getResourceUstring("=OperatorDisplay"),
                            *getResourceUstring("=OperatorSearchKey") ));
    operatorTypes->push_back(std::make_pair(
                            *getResourceUstring("<=OperatorDisplay"),
                            *getResourceUstring("<=OperatorSearchKey") ));
    operatorTypes->push_back(std::make_pair(
                            *getResourceUstring(">=OperatorDisplay"),
                            *getResourceUstring(">=OperatorSearchKey") ));
}

