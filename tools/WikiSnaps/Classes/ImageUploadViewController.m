//
//  ImageUploadViewController.m
//  WikiSnaps
//
//  Created by Derk-Jan Hartman on 23-01-11.
//  Copyright 2011 Derk-Jan Hartman
//
//  Dual-licensed MIT and BSD
//  Based on Photopicker (MIT)

#import "ImageUploadViewController.h"
#import "Configuration.h"


@implementation ImageUploadViewController

@synthesize upload;


// The designated initializer.  Override if you create the controller programmatically and want to perform customization that is not appropriate for viewDidLoad.
/*
- (id)initWithNibName:(NSString *)nibNameOrNil bundle:(NSBundle *)nibBundleOrNil {
    self = [super initWithNibName:nibNameOrNil bundle:nibBundleOrNil];
    if (self) {
        // Custom initialization.
    }
    return self;
}
*/


// Implement viewDidLoad to do additional setup after loading the view, typically from a nib.
- (void)viewDidLoad {
    [super viewDidLoad];
    
    uploadOverlayImage.image = self.upload.originalImage;
    uploadProgressMessage.text = NSLocalizedString( @"uploading", @"Upload progress message" );
    uploadProgress.progress = 0.0f;

    // view.frame = CGRectMake(0, 20, 320, 460);
    // [[UIApplication sharedApplication].keyWindow addSubview:PhotoOverlay];

    // Start the actual upload
    [self.upload setDelegate: self];
    [self.upload uploadImage];
}

- (void) viewWillAppear:(BOOL)animated{
    [[self navigationController] setNavigationBarHidden:YES animated:YES];
}

- (void) viewWillDisappear:(BOOL)animated{
    [[self navigationController] setNavigationBarHidden:NO animated:YES];
}


/*
// Override to allow orientations other than the default portrait orientation.
- (BOOL)shouldAutorotateToInterfaceOrientation:(UIInterfaceOrientation)interfaceOrientation {
    // Return YES for supported orientations.
    return (interfaceOrientation == UIInterfaceOrientationPortrait);
}
*/

- (void)didReceiveMemoryWarning {
    // Releases the view if it doesn't have a superview.
    [super didReceiveMemoryWarning];
    
    // Release any cached data, images, etc. that aren't in use.
}

- (void)viewDidUnload {
    [super viewDidUnload];
    // Release any retained subviews of the main view.
    // e.g. self.myOutlet = nil;
}


- (void)dealloc {
    self.upload = nil;
    [super dealloc];
}

- (IBAction)onCancelUploadClicked: (id)sender {
    /* FIXME cancel all api events on upload */
    [self.navigationController popToRootViewControllerAnimated:YES];
}


- (void)uploadSucceeded {
    UIAlertView *alert =
    [[UIAlertView alloc] initWithTitle: NSLocalizedString( @"Upload succeeded", @"Title for upload succeeded alert" )
                                                        message: nil
                                                        delegate: self
                                     cancelButtonTitle: NSLocalizedString( @"Cancel", @"" )
                                     otherButtonTitles: NSLocalizedString( @"Show upload", @"Title for Show upload button in alert view after upload succeeded" ),nil];
    [alert show];
    [alert release];
}

- (void)uploadFailed:(NSString *)error {
    NSLog(@"%@", error);
    
    UIAlertView *alert =
    [[UIAlertView alloc] initWithTitle: NSLocalizedString( @"Upload failed", @"Title for upload failed alert" )
                                                        message: error
                                                        delegate: self
                                     cancelButtonTitle: NSLocalizedString( @"OK", @"" )
                                     otherButtonTitles: nil];
    [alert show];
    [alert release];
    [self.navigationController popToRootViewControllerAnimated:YES];
}

- (void) alertView: (UIAlertView *) alertView clickedButtonAtIndex: (NSInteger) buttonIndex {
    if( buttonIndex == 0 ) {
        NSLog( @"Cancel" );
    } else if ( buttonIndex == 1 ) {
        [[UIApplication sharedApplication] openURL:[NSURL URLWithString: [NSString stringWithFormat: COMMONS_DESTINATION_URL, [self.upload.imageTitle stringByAddingPercentEscapesUsingEncoding:NSUTF8StringEncoding]]]];
    }
    [self.navigationController popToRootViewControllerAnimated:YES];
} // clickedButtonAtIndex


@end
