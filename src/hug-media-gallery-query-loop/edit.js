import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { 
    useBlockProps, 
    InspectorControls, 
    BlockControls, 
    AlignmentControl 
} from '@wordpress/block-editor';
import { 
    PanelBody, 
    SelectControl, 
    RangeControl, 
    ToggleControl, 
    Placeholder 
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import ServerSideRender from '@wordpress/server-side-render';

// Custom hook to fetch the taxonomy and categories
const useMediaTaxonomy = () => {
    const [categories, setCategories] = useState(null);
    const [isLoading, setIsLoading] = useState(true);
    const [hasCategories, setHasCategories] = useState(false);
    const [taxonomySlug, setTaxonomySlug] = useState('media_category');

    useEffect(() => {
        let isMounted = true;
        apiFetch({ path: '/hug/v1/media-taxonomy-slug' })
            .then(data => data.slug || 'media_category')
            .then(slug => {
                if (!isMounted) return;
                setTaxonomySlug(slug);
                return apiFetch({ path: `/wp/v2/${slug}?context=view&per_page=-1` });
            })
            .then(terms => {
                if (!isMounted) return;
                const termsArray = Array.isArray(terms) ? terms : [];
                setCategories(termsArray);
                setIsLoading(false);
                setHasCategories(termsArray && termsArray.length > 0);
            })
            .catch(err => {
                if (isMounted) {
                    console.error("Failed to fetch media category data:", err);
                    setCategories([]);
                    setIsLoading(false);
                    setHasCategories(false);
                }
            });
        return () => { isMounted = false; };
    }, []);

    // CHANGE 1: Updated the default placeholder label from "All Categories" to "Select a Category"
    const options = [{ label: __('Select a Category', 'hug-media-gallery-query-loop'), value: "" }];
    if (categories) {
        categories.forEach(cat => {
            options.push({ label: cat.name, value: cat.slug });
        });
    }

    return { categoryOptions: options, isLoading, hasCategories, taxonomySlug };
};

export default function Edit({ attributes, setAttributes }) {
    const { categoryOptions, isLoading, hasCategories } = useMediaTaxonomy();
    const { layoutStyle, columns, align, mediaTaxonomy, sortOption, order, imageSize, useLightbox, showTitles } = attributes;

    const blockProps = useBlockProps({
        className: `hug-media-query-loop-editor align${align || ''}`
    });

    const isFixedLayout = layoutStyle === 'tiled' || layoutStyle === 'fancy';
    const lightboxStatus = useLightbox ? "Yes" : "No";

    return (
        <>
            <InspectorControls>
                <PanelBody title={__("Media Gallery Filters", "hug-media-gallery-query-loop")} initialOpen={true}>
                    <SelectControl
                        label={__("Media Category", "hug-media-gallery-query-loop")}
                        options={isLoading ? [{ label: 'Loading Categories...', value: '' }] : categoryOptions}
                        value={mediaTaxonomy || ''}
                        onChange={(val) => setAttributes({ mediaTaxonomy: val })}
                        disabled={isLoading || !hasCategories}
                    />
                    <SelectControl
						label={__('Sort By', 'hug-media-gallery-query-loop')}
						value={sortOption}
						options={[
							{ label: __('Date', 'hug-media-gallery-query-loop'), value: 'date' },
							{ label: __('Title', 'hug-media-gallery-query-loop'), value: 'title' },
							{ label: __('Order', 'hug-media-gallery-query-loop'), value: 'menu_order' },
						]}
						onChange={(value) => setAttributes( {sortOption: value})}
                    />
					<SelectControl
						label={__('Order Direction', 'hug-media-gallery-query-loop')}
						value={order}
						options = {[
							{ label: __('Descending', 'hug-media-gallery-query-loop'), value: 'DESC' },
							{label: __('Ascending', 'hug-media-gallery-query-loop'), value: 'ASC' },
						]}
						onChange={(value) => setAttributes({ order: value })}
					/>
                    <SelectControl
                        label={__("Image Resolution", "hug-media-gallery-query-loop")}
                        help={__("Sets the size for all images displayed.", "hug-media-gallery-query-loop")}
                        value={imageSize || 'medium'}
                        onChange={(val) => setAttributes({ imageSize: val })}
                        options={[
                            { label: "Small (Thumbnail)", value: "thumbnail" },
                            { label: "Medium", value: "medium" },
                            { label: "Large", value: "large" },
                            { label: "Full", value: "full" }
                        ]}
                    />
                    <SelectControl
                        label={__("Layout Style", "hug-media-gallery-query-loop")}
                        value={layoutStyle || 'grid'}
                        onChange={(val) => setAttributes({ layoutStyle: val })}
                        options={[
                            { label: "Grid", value: "grid" },
                            { label: "Masonry", value: "masonry" },
                            { label: "Tiled (Dynamic Span)", value: "tiled" },
                            { label: "Fancy (Recursive Split)", value: "fancy" }
                        ]}
                    />
                    {!isFixedLayout && (
                        <RangeControl
                            label={__("Number of columns", "hug-media-gallery-query-loop")}
                            value={columns || 3}
                            onChange={(val) => setAttributes({ columns: val })}
                            min={1}
                            max={6}
                        />
                    )}
                    <ToggleControl
                        label={__("Enable Lightbox", "hug-media-gallery-query-loop")}
                        checked={!!useLightbox}
                        onChange={(val) => setAttributes({ useLightbox: val })}
                    />
                    <ToggleControl
                        label={__("Show Image Titles", "hug-media-gallery-query-loop")}
                        checked={!!showTitles}
                        onChange={(val) => setAttributes({ showTitles: val })}
                    />
                </PanelBody>
            </InspectorControls>

            <BlockControls>
                <AlignmentControl
                    value={align}
                    onChange={(val) => setAttributes({ align: val })}
                />
            </BlockControls>

            <div {...blockProps}>
                {/* CHANGE 2: Conditional Rendering. If mediaTaxonomy is empty, show the Placeholder. Otherwise, run ServerSideRender */}
                { ! mediaTaxonomy ? (
                    <Placeholder 
                        icon="images-alt2"
                        label={__("Select a Category", "hug-media-gallery-query-loop")}
                        instructions={__("Please select a media category from the block settings to display images.", "hug-media-gallery-query-loop")}
                    />
                ) : (
                    <ServerSideRender
                        block="create-block/hug-media-gallery-query-loop"
                        attributes={attributes}
                        EmptyResponsePlaceholder={() => (
                            <Placeholder 
                                label={__("No Images Found", "hug-media-gallery-query-loop")}
                                instructions={__("No images were found for the selected category.", "hug-media-gallery-query-loop")}
                            />
                        )}
                    />
                ) }
            </div>
        </>
    );
}