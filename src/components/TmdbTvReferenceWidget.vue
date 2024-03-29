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
	<div class="tv-reference">
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
		<div class="tv-wrapper">
			<div v-if="richObject.image_url" class="poster-wrapper">
				<img :src="richObject.image_url">
			</div>
			<div class="content"
				:class="{ expanded: expandContent }"
				@click="expandContent = !expandContent">
				<div class="title">
					<strong>
						<a :href="richObject.tmdb_url" target="_blank" class="line">
							<TelevisionClassicIcon :size="20" class="icon" />
							{{ richObject.formatted_name }}
						</a>
					</strong>
				</div>
				<p v-if="richObject.formatted_first_air_date" class="release-date line">
					<CalendarIcon :size="20" class="icon" />
					{{ t('integration_tmdb', 'First air date: {date}' , { date: richObject.formatted_first_air_date }) }}
				</p>
				<p v-if="richObject.formatted_last_air_date" class="release-date line">
					<CalendarBlankOutlineIcon :size="20" class="icon" />
					{{ t('integration_tmdb', 'Last air date: {date}' , { date: richObject.formatted_last_air_date }) }}
				</p>
				<p v-if="genres" class="line">
					<ShapeIcon :size="20" class="icon" />
					{{ genres }}
				</p>
				<p v-if="nbEpisodes && nbSeasons" class="line">
					<InformationOutlineIcon :size="20" class="icon" />
					{{ nbEpisodes }}{{ nbSeasons }}
				</p>
				<p v-if="richObject.tagline" class="tagline">
					{{ richObject.tagline }}
				</p>
				<p v-if="richObject.overview" class="overview">
					{{ richObject.overview }}
				</p>
			</div>
		</div>
	</div>
</template>

<script>
import ShapeIcon from 'vue-material-design-icons/Shape.vue'
import InformationOutlineIcon from 'vue-material-design-icons/InformationOutline.vue'
import TelevisionClassicIcon from 'vue-material-design-icons/TelevisionClassic.vue'
import OpenInNewIcon from 'vue-material-design-icons/OpenInNew.vue'
import CalendarIcon from 'vue-material-design-icons/Calendar.vue'
import CalendarBlankOutlineIcon from 'vue-material-design-icons/CalendarBlankOutline.vue'

import TmdbIcon from './icons/TmdbIcon.vue'

import { generateUrl } from '@nextcloud/router'

import Tooltip from '@nextcloud/vue/dist/Directives/Tooltip.js'
import Vue from 'vue'
Vue.directive('tooltip', Tooltip)

export default {
	name: 'TmdbTvReferenceWidget',

	components: {
		TmdbIcon,
		OpenInNewIcon,
		CalendarIcon,
		CalendarBlankOutlineIcon,
		TelevisionClassicIcon,
		ShapeIcon,
		InformationOutlineIcon,
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
		genres() {
			if (this.richObject.genres?.length > 0) {
				return this.richObject.genres.map(g => g.name).join(', ')
			}
			return null
		},
		nbEpisodes() {
			if (this.richObject.number_of_episodes) {
				return t('integration_tmdb', '{nb} episodes', { nb: this.richObject.number_of_episodes })
			}
			return null
		},
		nbSeasons() {
			if (this.richObject.number_of_seasons) {
				return ' • ' + t('integration_tmdb', '{nb} seasons', { nb: this.richObject.number_of_seasons })
			}
			return null
		},
	},

	methods: {
	},
}
</script>

<style scoped lang="scss">
.tv-reference {
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

	.tv-wrapper {
		width: 100%;
		display: flex;
		align-items: start;

		.line {
			display: flex;
			align-items: center;

			> .icon {
				margin: 0 12px 0 4px;
			}
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
			max-height: calc(300px - 2 * 12px);
			&.expanded {
				max-height: 650px;
				overflow: scroll;
				scrollbar-width: auto;
			}
			.overview {
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
