export const init = (choices) => {
    var selectseries = document.getElementById('id_series');
    var selectepisodes = document.getElementById('id_episode');

    selectseries.addEventListener('change', function() {
        var series = this.value;
        document.getElementById('id_ocinstanceid').value = this.value.substring(this.value.lastIndexOf('_') + 1, this.value.length);
        document.getElementById('id_opencastid').value = this.value.substring(0, this.value.lastIndexOf('_'));
        document.getElementById('id_opencastid').dispatchEvent(new Event('change'));

        Array.from(selectepisodes.options).forEach(function(option) {
            if (option.value in choices[series]) {
                option.hidden = false;
                option.hidden = false;
            } else {
                option.hidden = true;
                option.hidden = true;
            }
        });
        selectepisodes.value = 'allvideos';
    });

    selectepisodes.addEventListener('change', function() {
        document.getElementById('id_opencastid').value = this.value;
        document.getElementById('id_opencastid').dispatchEvent(new Event('change'));
    });

    // Initialize select values.
    var initOpencastid = document.getElementById('id_opencastid').value;
    if (initOpencastid) {
        selectepisodes.value = initOpencastid;
        var foundseries = false;
        for (const [seriesid, episodes] of Object.entries(choices)) {
            // Skip series from wrong Opencast instance.
            if (seriesid.substring(seriesid.lastIndexOf('_') + 1, seriesid.length) !==
                document.getElementById('id_ocinstanceid').value) {
                continue;
            }

            // ID is series.
            if (initOpencastid === seriesid.substring(0, seriesid.lastIndexOf('_'))) {
                selectseries.value = seriesid;
                selectepisodes.value = 'allvideos';
                foundseries = true;
                break;
            }

            if (initOpencastid in episodes) {
                selectseries.value = seriesid;
                foundseries = true;
                break;
            }
        }
        if (!foundseries) {
            selectseries.value = 'invalid';
        }

    } else {
        // Use defaults.
        selectseries.dispatchEvent(new Event('change'));
    }

    // Disabled values should also be submitted in this case.
    selectseries.form.addEventListener("submit", function() {
        document.getElementById('id_opencastid').disabled = false;
        document.getElementById('id_ocinstanceid').disabled = false;
    });
};