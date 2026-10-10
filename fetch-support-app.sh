#!/bin/bash

# setup strict mode
set -euo pipefail

NC_RELEASE_URL="https://download.nextcloud.com/server/releases/"
EXTRACT="nextcloud-.*.tar.bz2"
PATH_TO_SUPPORT="nextcloud/apps/support"
INITIAL_VERSION="14.99.99"

# compare two semver versions
function compare_semver_version() {
    local version1=$1
    local version2=$2

    if [[ $version1 == $version2 ]]
    then
        return 0
    fi

    local IFS=.
    local i ver1=($version1) ver2=($version2)
    for ((i=0; i<${#ver1[@]}; i++))
    do
        if ((${ver1[i]} > ${ver2[i]}))
        then
            return 1
        elif ((${ver1[i]} < ${ver2[i]}))
        then
            return 2
        fi
    done
    return 0
}

function git() {
    command git -c "user.name=Nextcloud" -C ${PATH_TO_SUPPORT} "$@"
}

if [ ! -d "${PATH_TO_SUPPORT}" ]; then
    mkdir -p ${PATH_TO_SUPPORT}
    git init --quiet
else
    INITIAL_VERSION=$(
        git log -1 --pretty=format:%s \
      | grep -E "Nextcloud [0-9]+\.[0-9]+\.[0-9]+" \
      | sed -E "s/Nextcloud ([0-9]+\.[0-9]+\.[0-9]+).*/\1/"
    )
fi

IFS=$'\n'
for line in $(curl -s ${NC_RELEASE_URL} | grep -E ${EXTRACT}\")
do
    RELEASE=$(echo ${line} | sed -E "s/.*($EXTRACT).*/\1/")
    DATE=$(echo ${line} | sed -E "s/.*([0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}).*/\1/")
    NC_VERSION=$(echo ${RELEASE} | sed -E "s/nextcloud-(.*).tar.bz2/\1/")

    # check if the version is greater than the initial version
    if [[ -n $INITIAL_VERSION ]]
    then
        set +e
        compare_semver_version $NC_VERSION $INITIAL_VERSION
        if [[ $? == 2 ]]
        then
            set -e
            echo "Skipping ${NC_VERSION}"
            continue
        fi
        set -e
    fi

    find ${PATH_TO_SUPPORT} -mindepth 1 -maxdepth 1 ! -name '.git' -exec rm -rf {} +
    echo "Downloading ${RELEASE}"
    
    curl -s ${NC_RELEASE_URL}${RELEASE} | tar -xj --no-same-owner ${PATH_TO_SUPPORT}
    rm -rf ${PATH_TO_SUPPORT}/appinfo/signature.json

    # run outside [[ ]] so a git failure aborts the script
    STATUS=$(git status --porcelain)
    if [[ -n $STATUS ]]
    then
        APP_VERSION=$(
            grep -E "<version>" ${PATH_TO_SUPPORT}/appinfo/info.xml \
          | sed -E "s/.*<version>(.*)<\/version>.*/\1/"
        )

        echo "Committing ${NC_VERSION}/${APP_VERSION}"
        git add -A
        git commit --quiet --date="${DATE}" \
            --message="Nextcloud ${NC_VERSION} - Support ${APP_VERSION}"

        git tag --force ${APP_VERSION}
    else
        echo "No changes in ${RELEASE}"
    fi
done
