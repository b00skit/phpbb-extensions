<script defer>
    scripts = document.getElementsByClassName("postas");
    for (i = 0; i < scripts.length; i++) {
    }
</script>
<script defer class="postas {SIMPLETEXT}{IDENTIFIER}">
    function insertAfter(referenceNode, newNode) {
        referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling);
    }
    document.currentScript.setAttribute("id", i);
    var regName = /^[^Â±!@Â£$%^&*_+Â§Â¡â‚¬#Â¢Â§Â¶â€¢ÂªÂºÂ«\\/<>?:;|=.,]{1,28}$/;
    if (!regName.test("{SIMPLETEXT}")) {
        // do nothing
    } else {
        if (window.location.href.includes("search.php")) {
            //
        } else {
        postShell = document.getElementsByClassName("postas")[i].parentElement;
        if (postShell.parentElement.tagName == "BLOCKQUOTE") {
            postShell.getElementsByTagName("cite")[0].getElementsByTagName("a")[0].innerHTML = "{SIMPLETEXT}";
        } else {
        post = postShell.parentElement.parentElement.parentElement;
        elem = document.createElement("span");
        realRank = document.createElement("dd");
        if (window.location.href.includes("posting.php")) {
            author = post.getElementsByClassName("postbody")[i+1].getElementsByClassName("username-coloured")[0];
        } else if (window.location.href.includes("viewtopic") || window.location.href.includes("i=pm")) {
            author = post.getElementsByClassName("postbody")[0].getElementsByClassName("username-coloured")[0];
            sideAuthor = post.getElementsByClassName("postprofile")[0].getElementsByClassName("username-coloured")[0];
            sideRank = post.getElementsByClassName("profile-rank")[0];
            fetchJoin = post.getElementsByClassName("postprofile")[0].getElementsByClassName("profile-joined")[0];
            fetchRank = sideRank.getElementsByTagName("img")[0].getAttribute('src');
        }
        elem.innerHTML = " (" + author.innerHTML + ")";
        elem.style.fontWeight = "normal";
        insertAfter(author, elem);
        if ((window.location.href.includes("viewtopic") || window.location.href.includes("i=pm")) && (window.innerWidth <= 700)) {
            newElem = elem;
            newElem.innerHTML = "<br>(" + author.innerHTML + ")";
            insertAfter(sideAuthor, newElem);
        }
        author.innerHTML = "{SIMPLETEXT}";
        elem = undefined;
        delete (elem);

        if (window.location.href.includes("viewtopic") || window.location.href.includes("i=pm")) {
            switch (true) {
                default:
                    realRank.innerHTML = '<strong>Actual Rank:</strong> <span style="font-weight: normal">None</span>';
                    break;
                case fetchRank.includes("NAZ_12_captain.png"):
                    realRank.innerHTML = '<strong>Actual Rank:</strong> <span style="font-weight: normal">Captain</span>';
                    break;
                case fetchRank.includes("NAZ_12_commander.png"):
                    realRank.innerHTML = '<strong>Actual Rank:</strong> <span style="font-weight: normal">Commander</span>';
                    break;
                case fetchRank.includes("09CHIEFES.png.png"):
                    realRank.innerHTML = '<strong>Actual Rank:</strong> <span style="font-weight: normal">Division Chief</span>';
                    break;
                case fetchRank.includes("10ASHER.png"):
                    realRank.innerHTML = '<strong>Actual Rank:</strong> <span style="font-weight: normal">Assistant Sheriff</span>';
                    break;
                case fetchRank.includes("11UNDERSHER.png"):
                    realRank.innerHTML = '<strong>Actual Rank:</strong> <span style="font-weight: normal">Undersheriff</span>';
                    break;
                case fetchRank.includes("12SHERIFFCLUSTER.png"):
                    realRank.innerHTML = '<strong>Actual Rank:</strong> <span style="font-weight: normal">Sheriff</span>';
                    break;
                case fetchRank.includes("NAZ_10_sergeant.png"):
                    realRank.innerHTML = '<strong>Actual Rank:</strong> <span style="font-weight: normal">Sergeant</span>';
                    break;
                case fetchRank.includes("NAZ_11_lieutenant.png"):
                    realRank.innerHTML = '<strong>Actual Rank:</strong> <span style="font-weight: normal">Lieutenant</span>';
                    break;
                case fetchRank.includes("NAZ_05_ds.png"):
                    realRank.innerHTML = '<strong>Actual Rank:</strong> <span style="font-weight: normal">Deputy Sheriff</span>';
                    break;
                case fetchRank.includes("NAZ_06_ds_b1.png"):
                    realRank.innerHTML = '<strong>Actual Rank:</strong> <span style="font-weight: normal">Deputy Sheriff (Bonus I)</span>';
                    break;
                case fetchRank.includes("NAZ_07_ds_b2.png"):
                    realRank.innerHTML = '<strong>Actual Rank:</strong> <span style="font-weight: normal">Deputy Sheriff (Bonus II)</span>';
                    break;
                case fetchRank.includes("NAZ_07_ds_b2_mfto.png"):
                    realRank.innerHTML = '<strong>Actual Rank:</strong> <span style="font-weight: normal">Deputy Sheriff (MFTO)</span>';
                    break;    
                case fetchRank.includes("NAZ_02_C_staff.png"):
                    realRank.innerHTML = '<strong>Actual Rank:</strong> <span style="font-weight: normal">Civilian Staff</span>';
                    break;  
                case fetchRank.includes("17INMATE.png"):
                    realRank.innerHTML = '<strong>Actual Rank:</strong> <span style="font-weight: normal">Inmate</span>';
                    break;    
                case fetchRank.includes("20JUDICIALLIAISON.png"):
                    realRank.innerHTML = '<strong>Actual Rank:</strong> <span style="font-weight: normal">Judicial Liaison</span>';
                    break;  
                case fetchRank.includes("21DISTRICTATTORNEY.png"):
                    realRank.innerHTML = '<strong>Actual Rank:</strong> <span style="font-weight: normal">District Attorney\'s Office</span>';
                    break;    
                case fetchRank.includes("PGU_04_C_S.png"):
                    realRank.innerHTML = '<strong>Actual Rank:</strong> <span style="font-weight: normal">Civilian Supervisor</span>';
                    break;
                case fetchRank.includes("PGU_05_C_M.png"):
                    realRank.innerHTML = '<strong>Actual Rank:</strong> <span style="font-weight: normal">Civilian Manager</span>';
                    break;
                case fetchRank.includes("PGU_01_C_1.png"):
                    realRank.innerHTML = '<strong>Actual Rank:</strong> <span style="font-weight: normal">Civilian Staff I</span>';
                    break;
                case fetchRank.includes("PGU_02_C_2.png"):
                    realRank.innerHTML = '<strong>Actual Rank:</strong> <span style="font-weight: normal">Civilian Staff II</span>';
                    break;
                case fetchRank.includes("PGU_03_C_PS.png"):
                    realRank.innerHTML = '<strong>Actual Rank:</strong> <span style="font-weight: normal">Civilian Supervisor Probationary</span>';
                    break;
                    
            }
            insertAfter(fetchJoin, realRank);
        }  

        switch ("{IDENTIFIER}") {
            case "CPT":
                author.style.color = "#284f6e";
                if (window.location.href.includes("viewtopic") || window.location.href.includes("i=pm")) {
                    sideAuthor.style.color = "#284f6e";
                    sideAuthor.innerHTML = "{SIMPLETEXT}";
                    sideRank.innerHTML = 'Command Staff<br><img src="./images/ranks/NAZ_12_captain.png" alt="Command Staff" title="Command Staff">';
                }
                break;
            case "CMDR":
                author.style.color = "#284f6e";
                if (window.location.href.includes("viewtopic") || window.location.href.includes("i=pm")) {
                    sideAuthor.style.color = "#284f6e";
                    sideAuthor.innerHTML = "{SIMPLETEXT}";
                    sideRank.innerHTML = 'Command Staff<br><img src="./images/ranks/NAZ_12_commander.png" alt="Command Staff" title="Command Staff">';
                }
                break;

            case "SGT":
                author.style.color = "#990000";
                if (window.location.href.includes("viewtopic") || window.location.href.includes("i=pm")) {
                    sideAuthor.style.color = "#990000";
                    sideAuthor.innerHTML = "{SIMPLETEXT}";
                    sideRank.innerHTML = 'Supervisory Staff<br><img src="./images/ranks/NAZ_10_sergeant.png" alt="Supervisory Staff" title="Supervisory Staff">';
                }
                break;
            case "LT":
                author.style.color = "#990000";
                if (window.location.href.includes("viewtopic") || window.location.href.includes("i=pm")) {
                    sideAuthor.style.color = "#990000";
                    sideAuthor.innerHTML = "{SIMPLETEXT}";
                    sideRank.innerHTML = 'Supervisory Staff<br><img src="./images/ranks/NAZ_11_lieutenant.png" alt="Supervisory Staff" title="Supervisory Staff">';
                }
                break;

            case "DS":
                author.style.color = "#084f0a";
                if (window.location.href.includes("viewtopic") || window.location.href.includes("i=pm")) {
                    sideAuthor.style.color = "#084f0a";
                    sideAuthor.innerHTML = "{SIMPLETEXT}";
                    sideRank.innerHTML = 'Field Staff<br><img src="./images/ranks/NAZ_05_ds.png" alt="Field Staff" title="Field Staff">';
                }
                break;
            case "B1":
                author.style.color = "#084f0a";
                if (window.location.href.includes("viewtopic") || window.location.href.includes("i=pm")) {
                    sideAuthor.style.color = "#084f0a";
                    sideAuthor.innerHTML = "{SIMPLETEXT}";
                    sideRank.innerHTML = 'Field Staff<br><img src="./images/ranks/NAZ_06_ds_b1.png" alt="Field Staff" title="Field Staff">';
                }
                break;
            case "B2":
                author.style.color = "#084f0a";
                if (window.location.href.includes("viewtopic") || window.location.href.includes("i=pm")) {
                    sideAuthor.style.color = "#084f0a";
                    sideAuthor.innerHTML = "{SIMPLETEXT}";
                    sideRank.innerHTML = 'Field Staff<br><img src="./images/ranks/NAZ_07_ds_b2.png" alt="Field Staff" title="Field Staff">';
                }
                break;
            case "MFTO":
                author.style.color = "#084f0a";
                if (window.location.href.includes("viewtopic") || window.location.href.includes("i=pm")) {
                    sideAuthor.style.color = "#084f0a";
                    sideAuthor.innerHTML = "{SIMPLETEXT}";
                    sideRank.innerHTML = 'Field Staff<br><img src="./images/ranks/NAZ_07_ds_b2_mfto.png" alt="Field Staff" title="Field Staff">';
                }
                break;

            case "CIV":
                author.style.color = "#00BFFF";
                if (window.location.href.includes("viewtopic") || window.location.href.includes("i=pm")) {
                    sideAuthor.style.color = "#00BFFF";
                    sideAuthor.innerHTML = "{SIMPLETEXT}";
                    sideRank.innerHTML = 'Non-Sworn Staff<br><img src="./images/ranks/NAZ_02_C_staff.png" alt="Non-Sworn Staff" title="Non-Sworn Staff">';
                }
                break;

            case "INMATE":
                author.style.color = "#000000";
                if (window.location.href.includes("viewtopic") || window.location.href.includes("i=pm")) {
                    sideAuthor.style.color = "#000000";
                    sideAuthor.innerHTML = "{SIMPLETEXT}";
                    sideRank.innerHTML = 'Inmate<br><img src="./images/ranks/17INMATE.png" alt="Inmate" title="Inmate">';
                }
                break;

            case "JSA":
                author.style.color = "#000000";
                if (window.location.href.includes("viewtopic") || window.location.href.includes("i=pm")) {
                    sideAuthor.style.color = "#000000";
                    sideAuthor.innerHTML = "{SIMPLETEXT}";
                    sideRank.innerHTML = 'Outside Agency<br><img src="./images/ranks/20JUDICIALLIAISON.png" alt="Outside Agency" title="Outside Agency">';
                }
                break;

            case "DA":
                author.style.color = "#000000";
                if (window.location.href.includes("viewtopic") || window.location.href.includes("i=pm")) {
                    sideAuthor.style.color = "#000000";
                    sideAuthor.innerHTML = "{SIMPLETEXT}";
                    sideRank.innerHTML = 'Outside Agency<br><img src="./images/ranks/21DISTRICTATTORNEY.png" alt="Outside Agency" title="Outside Agency">';
                }
                break;

            case "CSUP":
                author.style.color = "#990000";
                if (window.location.href.includes("viewtopic") || window.location.href.includes("i=pm")) {
                    sideAuthor.style.color = "#990000";
                    sideAuthor.innerHTML = "{SIMPLETEXT}";
                    sideRank.innerHTML = 'Supervisory Staff<br><img src="./images/ranks/PGU_04_C_S.png" alt="Supervisory Staff" title="Supervisory Staff">';
                }
                break;

            case "CMGR":
                author.style.color = "#284f6e";
                if (window.location.href.includes("viewtopic") || window.location.href.includes("i=pm")) {
                    sideAuthor.style.color = "#284f6e";
                    sideAuthor.innerHTML = "{SIMPLETEXT}";
                    sideRank.innerHTML = 'Command Staff<br><img src="./images/ranks/PGU_05_C_M.png" alt="Command Staff" title="Command Staff">';
                }
                break;

            case "CIVI":
                author.style.color = "#00BFFF";
                if (window.location.href.includes("viewtopic") || window.location.href.includes("i=pm")) {
                    sideAuthor.style.color = "#00BFFF";
                    sideAuthor.innerHTML = "{SIMPLETEXT}";
                    sideRank.innerHTML = 'Professional Staff<br><img src="./images/ranks/PGU_01_C_1.png" alt="Professional Staff" title="Professional Staff">';
                }
                break;

            case "CIVII":
                author.style.color = "#00BFFF";
                if (window.location.href.includes("viewtopic") || window.location.href.includes("i=pm")) {
                    sideAuthor.style.color = "#00BFFF";
                    sideAuthor.innerHTML = "{SIMPLETEXT}";
                    sideRank.innerHTML = 'Professional Staff<br><img src="./images/ranks/PGU_02_C_2.png" alt="Professional Staff" title="Professional Staff">';
                }
                break;

            case "CSUPP":
                author.style.color = "#00BFFF";
                if (window.location.href.includes("viewtopic") || window.location.href.includes("i=pm")) {
                    sideAuthor.style.color = "#00BFFF";
                    sideAuthor.innerHTML = "{SIMPLETEXT}";
                    sideRank.innerHTML = 'Professional Staff<br><img src="./images/ranks/PGU_03_C_PS.png" alt="Professional Staff" title="Professional Staff">';
                }
                break;

            case "NONE":
                author.style.color = "#3f3f3f";
                author.style.fontWeight = "normal";
                if (window.location.href.includes("viewtopic") || window.location.href.includes("i=pm")) {
                    sideAuthor.innerHTML = "{SIMPLETEXT}";
                    sideAuthor.style.color = "#3f3f3f";
                    sideAuthor.style.fontWeight = "normal";
                    sideRank.innerHTML = '';
                }
                break;
            }
        }
    }
}
</script>