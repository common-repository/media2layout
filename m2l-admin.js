/*  Copyright 2009 Nick Eby (email:nick@pixelnix.com)

    This file is part of Media2Layout.
    
    Media2Layout is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.
    
    Media2Layout is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    
    You should have received a copy of the GNU General Public License
    along with Media2Layout.  If not, see <http://www.gnu.org/licenses/>.
*/

function add_m2lposition(theForm) {
    try {
	    jQuery.post(
	        jQuery(theForm).attr("action"), 
	        {
		        action: "m2l_add_position",
		        new_pos_lbl: theForm.new_pos_lbl.value
	        }, 
	        function(data) {
		        if (data['id']) {
                    jQuery('#m2l_new_position_tr').before(
                        '<tr><td>'+data['label']+'</td><td></td></tr>'
                    );
		        }
		        else {
					if (data['errors']) {
					    alert(data['errors']);
					}
		        }
		    },
		    "json"
	    );
    } catch (e) {}
    
    return false;
}

function del_m2lposition(id) {
    if (confirm('Really delete?')) {
	    try {
	        jQuery.post(
	            jQuery('#m2l_position_form').attr("action"),
	            {
	                action: "m2l_del_position",
	                position_id: id
	            },
	            function(data) {
	                if (data['errors'] != '') {
	                    alert(data['errors']);
	                }
	                else {
	                    jQuery('#m2l_position_tr_'+id).hide('slow');
	                    jQuery('#m2l_position_tr_'+id).remove();
	                }
	            },
	            "json"
	        );
	    } catch (e) {}
    }
    
    return false;
}