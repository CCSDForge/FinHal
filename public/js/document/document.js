    /**
     * Récupération de l'historique d'un dépôt
     * @param docid
     * @param div
     */
    function getDocHistory(docid, div)
    {
        $.ajax({
            url: '/view/history',
            type: "post",
            data: {docid:docid, limit:"moderate"},
            success: function(data) {
                $(div).find('.result').html(data);
            }
        });
    }