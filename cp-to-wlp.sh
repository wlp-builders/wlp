#!/bin/bash

# Get the script filename
script_name="$(basename "$0")"

# Define the old and new logo file names
old_logo="classicpress-logo.svg"
new_logo="wlp-logo.svg"
old_logo2="cp-logo-aqua.png"
new_logo2="wlp-logo-80.png"
old_logo3="classicpress-logo-white.svg"
new_logo3="wlp-logo-white.svg"
old_logo4="cp-logo-white.png"
new_logo4="wlp-logo-white-80.png"


old_logo5="classicpress-logo-dashicon-grey-on-transparent.svg"
new_logo5="wlp-logo-dashicon-grey-on-transparent.svg"



# Define old and new color codes
old_color1="#2271b1"
new_color1="#a91bbe"
old_color3="#135e96"
old_color4="#6827d3"
new_color3="#6827d3"
new_color4="#6d26d2"
old_color6="#0a4b78"
new_color6="#981ec3"
old_color7="#72aee6"
new_color7="#f6aaff"



# Define terms and new terms
old_term1="ClassicPress"
new_term1="WLP"
old_term2="classicpress"
new_term2="wlp"
old_term3="cp_"
new_term3="wlp_"



# Use grep to find all files containing the old logo
grep -ril "$old_term1" . | egrep -v ".ascend|.git|cp-to-wlp" | while read -r file; do
    # Check if the term exists in the file
    if grep -q "$old_term1" "$file"; then
        # Replace old term with new term
        sed -i "s|$old_term1|$new_term1|g" "$file"
        echo "Updated term in $file"
    fi
done
# Use grep to find all files containing the old logo
grep -ril "$old_term2" wp-admin/setup-config.php | egrep -v ".ascend|.git|cp-to-wlp" | while read -r file; do
    # Check if the term exists in the file
    if grep -q "$old_term2" "$file"; then
        # Replace old term with new term
        sed -i "s|$old_term2|$new_term2|g" "$file"
        echo "Updated term in $file"
    fi
done
# Use grep to find all files containing the old logo
grep -ril "$old_term3" wp-admin/setup-config.php | egrep -v ".ascend|.git|cp-to-wlp" | while read -r file; do
    # Check if the term exists in the file
    if grep -q "$old_term3" "$file"; then
        # Replace old term with new term
        sed -i "s|$old_term3|$new_term3|g" "$file"
        echo "Updated term in $file"
    fi
done

# Use grep to find all files containing the old logo
grep -ril "$old_logo" wp-*/ | egrep -v ".ascend|.git|cp-to-wlp" | while read -r file; do
    # Replace old logo with new logo
    sed -i "s|$old_logo|$new_logo|g" "$file"
    echo "Updated logo in $file"
done
# Use grep to find all files containing the old logo
grep -ril "$old_logo2" wp-*/ | egrep -v ".ascend|.git|cp-to-wlp" | while read -r file; do
    # Replace old logo2 with new logo2
    sed -i "s|$old_logo2|$new_logo2|g" "$file"
    echo "Updated logo2 in $file"
done
# Use grep to find all files containing the old logo
grep -ril "$old_logo3" wp-*/ | egrep -v ".ascend|.git|cp-to-wlp" | while read -r file; do
    # Replace old logo3 with new logo3
    sed -i "s|$old_logo3|$new_logo3|g" "$file"
    echo "Updated logo3 in $file"
done
# Use grep to find all files containing the old logo
grep -ril "$old_logo4" wp-*/ | egrep -v ".ascend|.git|cp-to-wlp" | while read -r file; do
    # Replace old logo4 with new logo4
    sed -i "s|$old_logo4|$new_logo4|g" "$file"
    echo "Updated logo4 in $file"
done
# Use grep to find all files containing the old logo
grep -ril "$old_logo5" wp-*/ | egrep -v ".ascend|.git|cp-to-wlp" | while read -r file; do
    # Replace old logo5 with new logo5
    sed -i "s|$old_logo5|$new_logo5|g" "$file"
    echo "Updated logo5 in $file"
done

# Use grep to find all files containing the old colors
grep -ril "$old_color1" . | egrep -v ".ascend|.git|cp-to-wlp" | while read -r file; do
    # Replace old color1 with new color1
    sed -i "s|$old_color1|$new_color1|g" "$file"
    echo "Updated $old_color1 to $new_color1 in $file"
done


# Use grep to find all files containing the second old color
grep -ril "$old_color3" . | egrep -v ".ascend|.git|cp-to-wlp" | while read -r file; do
    # Replace old color3 with new color3
    sed -i "s|$old_color3|$new_color3|g" "$file"
    echo "Updated $old_color3 to $new_color3 in $file"
done

# Use grep to find all files containing the second old color
grep -ril "$old_color4" . | egrep -v ".ascend|.git|cp-to-wlp" | while read -r file; do
    # Replace old color4 with new color4
    sed -i "s|$old_color4|$new_color4|g" "$file"
    echo "Updated $old_color4 to $new_color4 in $file"
done

# Use grep to find all files containing the second old color
grep -ril "$old_color6" . | egrep -v ".ascend|.git|cp-to-wlp"  | while read -r file; do
    # Replace old color6 with new color6
    sed -i "s|$old_color6|$new_color6|g" "$file"
    echo "Updated $old_color6 to $new_color6 in $file"
done

# Use grep to find all files containing the second old color
grep -ril "$old_color7" . | egrep -v ".ascend|.git|cp-to-wlp"  | while read -r file; do
    # Replace old color7 with new color7
    sed -i "s|$old_color7|$new_color7|g" "$file"
    echo "Updated $old_color7 to $new_color7 in $file"
done
echo "Replacement complete."

