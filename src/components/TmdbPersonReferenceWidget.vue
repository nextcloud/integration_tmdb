<!--
  - @copyright Copyright (c) 2023 Julien Veyssier <eneiluj@posteo.net>
  -
  - @author 2022 Julien Veyssier <eneiluj@posteo.net>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
	<div class="person-reference">
		<div v-if="isError">
			<h3 class="error-title">
				<TmdbIcon :size="20" class="icon" />
				<span>{{ t('integration_tmdb', 'TMDB API error') }}</span>
			</h3>
			<p v-if="richObject.body?.message"
				class="widget-error">
				{{ richObject.body?.message }}
			</p>
			<p v-else
				class="widget-error">
				{{ t('integration_tmdb', 'Unknown error') }}
			</p>
			<a :href="settingsUrl" class="settings-link external" target="_blank">
				<OpenInNewIcon :size="20" class="icon" />
				{{ t('integration_tmdb', 'TMDB connected accounts settings') }}
			</a>
		</div>
		<div class="person-wrapper">
			<div v-if="richObject.image_url" class="poster-wrapper">
				<img :src="richObject.image_url">
			</div>
			<div class="content"
				:class="{ expanded: expandContent }"
				@click="expandContent = !expandContent">
				<div class="name">
					<strong>
						<a :href="richObject.tmdb_url" target="_blank" class="line">
							<FaceManIcon v-if="richObject.gender === 2" :size="20" class="icon" />
							<FaceWomanIcon v-else :size="20" class="icon" />
							{{ richObject.name }}
						</a>
					</strong>
				</div>
				<p v-if="richObject.birthday" class="dates line">
					<CalendarIcon :size="20" class="icon" />
					<span v-if="richObject.place_of_birth">
						{{ t('integration_tmdb', 'Born {date} at {place}' , { date: richObject.formatted_birthday, place: richObject.place_of_birth }) }}
					</span>
					<span v-else>
						{{ t('integration_tmdb', 'Born {date}' , { date: richObject.formatted_birthday }) }}
					</span>
				</p>
				<p v-if="richObject.deathday" class="dates line">
					<CalendarBlankOutlineIcon :size="20" class="icon" />
					<span v-if="richObject.deathday">
						{{ t('integration_tmdb', 'Dead {date}' , { date: richObject.formatted_deathday }) }}
					</span>
				</p>
				<p v-if="richObject.known_for_department" class="knownfor">
					{{ t('integration_tmdb', 'Known for {profession}' , { profession: richObject.known_for_department }) }}
				</p>
				<p v-if="richObject.biography" class="biography">
					{{ richObject.biography }}
				</p>
			</div>
		</div>
	</div>
</template>

<script>
import FaceManIcon from 'vue-material-design-icons/FaceMan.vue'
import FaceWomanIcon from 'vue-material-design-icons/FaceWoman.vue'
import OpenInNewIcon from 'vue-material-design-icons/OpenInNew.vue'
import CalendarIcon from 'vue-material-design-icons/Calendar.vue'
import CalendarBlankOutlineIcon from 'vue-material-design-icons/CalendarBlankOutline.vue'

import TmdbIcon from './icons/TmdbIcon.vue'

import { generateUrl } from '@nextcloud/router'

import Tooltip from '@nextcloud/vue/dist/Directives/Tooltip.js'
import Vue from 'vue'
Vue.directive('tooltip', Tooltip)

export default {
	name: 'TmdbPersonReferenceWidget',

	components: {
		TmdbIcon,
		OpenInNewIcon,
		CalendarIcon,
		CalendarBlankOutlineIcon,
		FaceManIcon,
		FaceWomanIcon,
	},

	props: {
		richObjectType: {
			type: String,
			default: '',
		},
		richObject: {
			type: Object,
			default: null,
		},
		accessible: {
			type: Boolean,
			default: true,
		},
	},

	data() {
		return {
			settingsUrl: generateUrl('/settings/user/connected-accounts#tmdb_prefs'),
			expandContent: false,
		}
	},

	computed: {
		isError() {
			return ['issue-error', 'pr-error'].includes(this.richObject.github_type)
		},
	},

	methods: {
	},
}
</script>

<style scoped lang="scss">
.person-reference {
	width: 100%;
	white-space: normal;

	a {
		padding: 0 !important;
		color: var(--color-main-text) !important;
		text-decoration: unset !important;
		cursor: pointer !important;
		&:hover {
			color: #58a6ff !important;
		}
	}

	.error-title {
		display: flex;
		align-items: center;
		font-weight: bold;
		margin-top: 0;
		.icon {
			margin-right: 8px;
		}
	}

	.person-wrapper {
		width: 100%;
		display: flex;
		align-items: start;

		.line {
			display: flex;
			align-items: center;

		}
		.icon {
			margin: 0 12px 0 4px;
		}

		.poster-wrapper {
			display: flex;
			img {
				max-width: 200px;
				max-height: 300px;
			}
		}

		.content {
			padding: 12px;
			max-height: 300px;
			&.expanded {
				max-height: 650px;
				overflow: scroll;
				scrollbar-width: auto;
			}
			.biography {
				cursor: ns-resize;
			}
		}
	}

	.settings-link {
		display: flex;
		align-items: center;
		.icon {
			margin-right: 4px;
		}
	}

	.widget-error {
		margin-bottom: 8px;
	}

	.spacer {
		flex-grow: 1;
	}
}
</style>
